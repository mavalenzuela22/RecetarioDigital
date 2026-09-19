<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveOrder
{
    public function __construct(private readonly ProductCosting $costing) {}

    public function save(array $data): Order
    {
        $normalized = $this->normalize($data);
        $existing = Order::where('request_key', $normalized['request_key'])->first();
        if ($existing) {
            return $this->replay($existing, $normalized['request_hash']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($normalized): Order {
                    $products = Product::query()
                        ->with(['recipe.latestVersion', 'latestProfile.components', 'latestPrice'])
                        ->whereIn('id', array_column($normalized['lines'], 'product_id'))
                        ->lockForUpdate()->get()->keyBy('id');

                    foreach ($normalized['lines'] as $position => $line) {
                        $product = $products->get($line['product_id']);
                        if (! $product || ! $product->active) {
                            throw ValidationException::withMessages([
                                "lines.$position.product_id" => 'Ese producto ya no está disponible. Conservamos el resto del pedido.',
                            ]);
                        }
                    }

                    $lineFacts = [];
                    $total = 0;
                    $allCostsComplete = true;
                    foreach ($normalized['lines'] as $position => $line) {
                        $product = $products->get($line['product_id']);
                        $priceMinor = $line['agreed_price_minor'];
                        $quantity = $line['quantity'];
                        $revenue = $this->multiply($priceMinor, $quantity, "lines.$position.agreed_price", 'El total del pedido excede el límite exacto permitido.');
                        $total = $this->add($total, $revenue, "lines.$position.agreed_price", 'El total del pedido excede el límite exacto permitido.');
                        $cost = $this->costing->current($product);
                        $unitCost = null;
                        $lineCost = null;
                        if ($cost['complete']) {
                            $unitCost = (int) $cost['unit_cost_micros'];
                            $lineCost = $this->multiply($unitCost, $quantity, "lines.$position.quantity", 'El costo del pedido excede el límite exacto permitido.');
                        } else {
                            $allCostsComplete = false;
                        }
                        $latestVersion = $product->recipe?->latestVersion;
                        $latestProfile = $product->latestProfile;
                        $latestPrice = $product->latestPrice;
                        $lineFacts[] = [
                            'product_id' => $product->id,
                            'product_name' => $latestVersion?->name ?? $product->recipe?->name ?? 'Producto',
                            'sale_unit' => $product->sale_unit,
                            'quantity' => $quantity,
                            'agreed_unit_price_minor' => $priceMinor,
                            'line_revenue_minor' => $revenue,
                            'product_price_id' => $latestPrice && (int) $latestPrice->price_minor === $priceMinor ? $latestPrice->id : null,
                            'recipe_version_id' => $latestVersion?->id,
                            'product_cost_profile_id' => $latestProfile?->id,
                            'attributable_unit_cost_micros' => $unitCost,
                            'attributable_line_cost_micros' => $lineCost,
                            'cost_complete' => $cost['complete'],
                        ];
                    }

                    $paid = ProductCosting::minor($normalized['advance'], 'advance');
                    if ($paid > $total) {
                        throw ValidationException::withMessages(['advance' => 'El anticipo debe estar entre $0 y el total.']);
                    }
                    $balance = $total - $paid;
                    $paymentState = $paid === 0 ? 'pending' : ($paid === $total ? 'paid' : 'partial');
                    $order = Order::create([
                        'request_key' => $normalized['request_key'],
                        'request_hash' => $normalized['request_hash'],
                        'customer_name' => $normalized['customer_name'],
                        'delivery_date' => $normalized['delivery_date'],
                        'delivery_time' => $normalized['delivery_time'],
                        'notes' => $normalized['notes'],
                        'fulfillment_state' => 'confirmed',
                        'payment_state' => $paymentState,
                        'total_minor' => $total,
                        'paid_minor' => $paid,
                        'balance_minor' => $balance,
                    ]);
                    $order->lines()->createMany($lineFacts);
                    if ($paid > 0) {
                        $order->payments()->create([
                            'amount_minor' => $paid,
                            'kind' => 'advance',
                            'effective_at' => now(),
                        ]);
                    }

                    return $order->load(['lines', 'payments']);
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                $existing = Order::where('request_key', $normalized['request_key'])->first();
                if ($existing) {
                    return $this->replay($existing, $normalized['request_hash']);
                }
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('No se pudo guardar el pedido.');
    }

    private function normalize(array $data): array
    {
        $customer = preg_replace('/\s+/u', ' ', trim((string) ($data['customer_name'] ?? '')));
        if ($customer === '') {
            throw ValidationException::withMessages(['customer_name' => 'Escribe el nombre del cliente.']);
        }
        $lines = [];
        $seen = [];
        foreach (array_values($data['lines'] ?? []) as $position => $line) {
            $productId = (int) ($line['product_id'] ?? 0);
            if (isset($seen[$productId])) {
                throw ValidationException::withMessages(["lines.$position.product_id" => 'Este producto ya está en el pedido. Ajusta la cantidad de la línea existente.']);
            }
            $seen[$productId] = true;
            $quantity = (string) ($line['quantity'] ?? '');
            if (! preg_match('/^[1-9][0-9]{0,8}$/', $quantity)) {
                throw ValidationException::withMessages(["lines.$position.quantity" => 'Escribe una cantidad entera mayor que cero.']);
            }
            $price = ProductCosting::minor($line['agreed_price'] ?? null, "lines.$position.agreed_price", true);
            $lines[] = [
                'product_id' => $productId,
                'quantity' => (int) $quantity,
                'agreed_price_minor' => $price,
            ];
        }
        if ($lines === []) {
            throw ValidationException::withMessages(['lines' => 'Agrega al menos un producto.']);
        }
        usort($lines, fn (array $left, array $right): int => $left['product_id'] <=> $right['product_id']);
        $advance = (string) ($data['advance'] ?? '0');
        ProductCosting::minor($advance, 'advance');
        $notes = trim((string) ($data['notes'] ?? ''));
        $payload = [
            'customer_name' => $customer,
            'lines' => $lines,
            'delivery_date' => (string) ($data['delivery_date'] ?? ''),
            'delivery_time' => (string) ($data['delivery_time'] ?? ''),
            'notes' => $notes === '' ? null : $notes,
            'advance_minor' => (string) ProductCosting::minor($advance, 'advance'),
        ];

        return [
            ...$payload,
            'advance' => $advance,
            'request_key' => strtolower((string) ($data['request_key'] ?? '')),
            'request_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
    }

    private function replay(Order $existing, string $hash): Order
    {
        if ($existing->request_hash !== $hash) {
            throw ValidationException::withMessages(['request_key' => 'Este pedido ya se registró con otros datos. Abre una nueva captura para guardar otro.']);
        }

        return $existing->load(['lines', 'payments']);
    }

    private function multiply(int $left, int $right, string $field, string $message): int
    {
        try {
            return ProductCosting::safeMultiply($left, $right, $message);
        } catch (ValidationException) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function add(int $left, int $right, string $field, string $message): int
    {
        try {
            return ProductCosting::safeAdd($left, $right, $message);
        } catch (ValidationException) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }
}
