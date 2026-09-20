<?php

namespace App\Services;

use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OperationalSummary
{
    private const ACTIVE_PRODUCTION_STATES = ['confirmed', 'in_preparation', 'ready'];

    public function today(): array
    {
        $date = $this->businessDate()->toDateString();
        $financialOrders = $this->ordersForRange($date, $date, null);
        $preparationOrders = $this->ordersForRange($date, $date, ['confirmed', 'in_preparation']);
        $deliveryOrders = $this->ordersForRange($date, $date, ['confirmed', 'in_preparation', 'ready']);
        $collectionOrders = $financialOrders->filter(fn (Order $order): bool => (int) $order->balance_minor > 0)->values();

        return [
            'business_date' => $date,
            'date_label' => $this->dateLabel($date),
            'title' => 'Hoy en tu cocina',
            'has_orders' => $financialOrders->isNotEmpty(),
            'production' => [
                'units_to_prepare' => (string) $this->totalPreparationUnits($preparationOrders),
                'groups' => $this->previewGroups($preparationOrders),
            ],
            'deliveries' => $deliveryOrders->map(fn (Order $order): array => $this->orderSummary($order))->values()->all(),
            'collections' => $collectionOrders->map(fn (Order $order): array => [
                'id' => $order->id,
                'customer_name' => $order->customer_name,
                'balance_label' => $this->formatMinor((int) $order->balance_minor),
                'url' => route('orders.show', $order),
            ])->values()->all(),
            'money' => $this->economics($financialOrders),
        ];
    }

    public function production(?string $from = null, ?string $to = null): array
    {
        $range = $this->normalizeRange($from, $to);
        $orders = $this->ordersForRange($range['from'], $range['to'], self::ACTIVE_PRODUCTION_STATES);

        return [
            'title' => 'Producción',
            'from' => $range['from'],
            'to' => $range['to'],
            'from_label' => $this->dateLabel($range['from']),
            'to_label' => $this->dateLabel($range['to']),
            'has_confirmed_orders' => $orders->contains(fn (Order $order): bool => $order->fulfillment_state === 'confirmed'),
            'has_orders' => $orders->isNotEmpty(),
            'groups' => $this->productionGroups($orders),
            'money' => $this->economics($orders),
        ];
    }

    public function normalizeRange(?string $from, ?string $to): array
    {
        $fromDate = $this->parseDate($from ?: $this->businessDate()->toDateString(), 'from');
        $toDate = $this->parseDate($to ?: $fromDate->toDateString(), 'to');
        if ($toDate->lessThan($fromDate)) {
            throw ValidationException::withMessages(['to' => 'La fecha final debe ser igual o posterior a la inicial.']);
        }

        return ['from' => $fromDate->toDateString(), 'to' => $toDate->toDateString()];
    }

    private function ordersForRange(string $from, string $to, ?array $states): Collection
    {
        $query = Order::query()
            ->with('lines')
            ->whereBetween('delivery_date', [$from, $to])
            ->orderBy('delivery_date')
            ->orderBy('delivery_time')
            ->orderBy('id');
        if ($states === null) {
            $query->where('fulfillment_state', '!=', 'cancelled');
        } else {
            $query->whereIn('fulfillment_state', $states);
        }

        return $query->get();
    }

    private function previewGroups(Collection $orders): array
    {
        return $this->groupLines($orders, false);
    }

    private function productionGroups(Collection $orders): array
    {
        return $this->groupLines($orders, true);
    }

    private function groupLines(Collection $orders, bool $includeReady): array
    {
        $groups = [];
        foreach ($orders as $order) {
            foreach ($order->lines as $line) {
                $key = (string) $line->product_id;
                if (! isset($groups[$key])) {
                    $groups[$key] = [
                        'product_id' => $line->product_id,
                        'name' => $line->product_name,
                        'total_units' => 0,
                        'units_to_prepare' => 0,
                        'ready_units' => 0,
                        'orders' => [],
                    ];
                }
                $quantity = (int) $line->quantity;
                $groups[$key]['total_units'] = ProductCosting::safeAdd($groups[$key]['total_units'], $quantity, 'Las piezas de producción exceden el límite exacto permitido.');
                if (in_array($order->fulfillment_state, ['confirmed', 'in_preparation'], true)) {
                    $groups[$key]['units_to_prepare'] = ProductCosting::safeAdd($groups[$key]['units_to_prepare'], $quantity, 'Las piezas de producción exceden el límite exacto permitido.');
                }
                if ($order->fulfillment_state === 'ready') {
                    $groups[$key]['ready_units'] = ProductCosting::safeAdd($groups[$key]['ready_units'], $quantity, 'Las piezas de producción exceden el límite exacto permitido.');
                }
                if ($includeReady || in_array($order->fulfillment_state, ['confirmed', 'in_preparation'], true)) {
                    $groups[$key]['orders'][] = $this->orderSummary($order, (int) $line->quantity, $line->product_name);
                }
            }
        }

        return array_values(array_map(function (array $group): array {
            $group['total_units'] = (string) $group['total_units'];
            $group['units_to_prepare'] = (string) $group['units_to_prepare'];
            $group['ready_units'] = (string) $group['ready_units'];
            return $group;
        }, $groups));
    }

    private function orderSummary(Order $order, ?int $quantity = null, ?string $productName = null): array
    {
        $summary = [
            'id' => $order->id,
            'customer_name' => $order->customer_name,
            'delivery_date' => $order->delivery_date,
            'delivery_date_label' => $this->dateLabel($order->delivery_date),
            'delivery_time' => substr($order->delivery_time, 0, 5),
            'quantity' => $quantity === null ? null : (string) $quantity,
            'fulfillment_state' => $order->fulfillment_state,
            'fulfillment_label' => $this->fulfillmentLabel($order->fulfillment_state),
            'url' => route('orders.show', $order),
            'ready_url' => $order->fulfillment_state === 'in_preparation' ? route('production.ready', $order) : null,
            'ready_request_key' => $order->fulfillment_state === 'in_preparation' ? (string) Str::uuid() : null,
        ];

        if ($productName !== null) {
            $summary['product_name'] = $productName;
        }

        return $summary;
    }

    private function economics(Collection $orders): array
    {
        $revenue = 0;
        $cost = 0;
        $complete = true;
        foreach ($orders as $order) {
            $revenue = ProductCosting::safeAdd($revenue, (int) $order->total_minor, 'La venta estimada excede el límite exacto permitido.');
            foreach ($order->lines as $line) {
                if (! $line->cost_complete || $line->attributable_line_cost_micros === null) {
                    $complete = false;
                    continue;
                }
                $cost = ProductCosting::safeAdd($cost, (int) $line->attributable_line_cost_micros, 'El costo estimado excede el límite exacto permitido.');
            }
        }
        $profit = $complete
            ? ProductCosting::safeAdd(ProductCosting::minorToMicros($revenue, 'La venta estimada excede el límite exacto permitido.'), -$cost, 'La ganancia estimada excede el límite exacto permitido.')
            : null;

        $balance = 0;
        foreach ($orders as $order) {
            $balance = ProductCosting::safeAdd($balance, (int) $order->balance_minor, 'El saldo pendiente excede el límite exacto permitido.');
        }

        return [
            'expected_revenue_minor' => (string) $revenue,
            'expected_revenue_label' => $this->formatMinor($revenue),
            'balance_minor' => (string) $balance,
            'balance_label' => $this->formatMinor($balance),
            'cost_complete' => $complete,
            'estimated_cost_micros' => $complete ? (string) $cost : null,
            'estimated_cost_label' => $complete ? $this->formatMicros($cost) : null,
            'estimated_profit_micros' => $profit === null ? null : (string) $profit,
            'estimated_profit_label' => $profit === null ? null : $this->formatMicros($profit),
            'profit_pending_label' => $complete ? null : 'Ganancia pendiente de calcular.',
        ];
    }

    private function totalPreparationUnits(Collection $orders): int
    {
        $total = 0;
        foreach ($orders as $order) {
            foreach ($order->lines as $line) {
                $total = ProductCosting::safeAdd($total, (int) $line->quantity, 'Las piezas de producción exceden el límite exacto permitido.');
            }
        }

        return $total;
    }

    private function businessDate(): CarbonImmutable
    {
        return CarbonImmutable::now(config('app.timezone'));
    }

    private function parseDate(string $value, string $field): CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone'));
        if ($date === false || $date->format('Y-m-d') !== $value) {
            throw ValidationException::withMessages([$field => 'Escribe una fecha local válida.']);
        }

        return $date;
    }

    private function dateLabel(string $date): string
    {
        [$year, $month, $day] = array_map('intval', explode('-', $date));
        $months = [1 => 'ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'sep', 'oct', 'nov', 'dic'];
        return sprintf('%d %s %d', $day, $months[$month], $year);
    }

    private function fulfillmentLabel(string $state): string
    {
        return [
            'confirmed' => 'Confirmado',
            'in_preparation' => 'En preparación',
            'ready' => 'Listo para entregar',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
        ][$state] ?? $state;
    }

    private function formatMinor(int $value): string
    {
        return $this->formatScaled($value, 2);
    }

    private function formatMicros(int $value): string
    {
        return $this->formatScaled($value, 6);
    }

    private function formatScaled(int $value, int $scale): string
    {
        $negative = $value < 0;
        $absolute = abs($value);
        $whole = intdiv($absolute, 10 ** $scale);
        $fraction = str_pad((string) ($absolute % (10 ** $scale)), $scale, '0', STR_PAD_LEFT);

        return ($negative ? '-' : '').'$'.number_format($whole, 0, '.', ',').'.'.$fraction.' MXN';
    }
}
