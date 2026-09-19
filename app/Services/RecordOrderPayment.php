<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderPayment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordOrderPayment
{
    public function record(Order $order, array $data): OrderPayment
    {
        $normalized = $this->normalize($order, $data);
        $existing = OrderPayment::where('request_key', $normalized['request_key'])->first();
        if ($existing) {
            return $this->replay($existing, $normalized['request_hash'], $order);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($order, $normalized): OrderPayment {
                    $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                    $existing = OrderPayment::where('request_key', $normalized['request_key'])->first();
                    if ($existing) {
                        return $this->replay($existing, $normalized['request_hash'], $lockedOrder);
                    }
                    if ($lockedOrder->fulfillment_state === 'cancelled') {
                        throw ValidationException::withMessages(['amount' => 'No puedes registrar un cobro en un pedido cancelado.']);
                    }
                    if ((int) $lockedOrder->balance_minor === 0) {
                        throw ValidationException::withMessages(['amount' => 'Este pedido ya está pagado.']);
                    }

                    $amount = $normalized['amount_minor'];
                    $balance = (int) $lockedOrder->balance_minor;
                    if ($amount > $balance) {
                        throw ValidationException::withMessages(['amount' => 'Escribe un importe mayor que $0 y no mayor que el saldo.']);
                    }
                    $paid = ProductCosting::safeAdd((int) $lockedOrder->paid_minor, $amount, 'El pago excede el límite exacto permitido.');
                    $newBalance = $balance - $amount;
                    $paymentState = $paid === 0 ? 'pending' : ($newBalance === 0 ? 'paid' : 'partial');
                    $payment = $lockedOrder->payments()->create([
                        'amount_minor' => $amount,
                        'kind' => 'collection',
                        'local_payment_date' => $normalized['payment_date'],
                        'request_key' => $normalized['request_key'],
                        'request_hash' => $normalized['request_hash'],
                        'effective_at' => now(),
                    ]);
                    $lockedOrder->update([
                        'paid_minor' => $paid,
                        'balance_minor' => $newBalance,
                        'payment_state' => $paymentState,
                    ]);

                    return $payment;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                $existing = OrderPayment::where('request_key', $normalized['request_key'])->first();
                if ($existing) {
                    return $this->replay($existing, $normalized['request_hash'], $order);
                }
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('No se pudo registrar el cobro.');
    }

    private function normalize(Order $order, array $data): array
    {
        $amount = ProductCosting::minor($data['amount'] ?? null, 'amount', true);
        $requestKey = strtolower((string) ($data['request_key'] ?? ''));
        $paymentDate = (string) ($data['payment_date'] ?? '');
        $payload = ['order_id' => $order->id, 'amount_minor' => (string) $amount, 'payment_date' => $paymentDate];

        return [
            ...$payload,
            'amount_minor' => $amount,
            'request_key' => $requestKey,
            'request_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
    }

    private function replay(OrderPayment $payment, string $hash, Order $order): OrderPayment
    {
        if ($payment->order_id !== $order->id || $payment->request_hash !== $hash) {
            throw ValidationException::withMessages(['request_key' => 'Este cobro ya se registró con otros datos. Abre el formulario de nuevo.']);
        }

        return $payment;
    }
}
