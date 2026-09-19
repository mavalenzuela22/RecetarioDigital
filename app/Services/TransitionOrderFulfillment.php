<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderFulfillmentEvent;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransitionOrderFulfillment
{
    public function transition(Order $order, string $toState, array $data): Order
    {
        if (! in_array($toState, ['delivered', 'cancelled'], true)) {
            throw new \InvalidArgumentException('Transición de entrega no autorizada.');
        }
        $normalized = $this->normalize($order, $toState, $data);
        $existing = OrderFulfillmentEvent::where('request_key', $normalized['request_key'])->first();
        if ($existing) {
            return $this->replay($existing, $normalized['request_hash'], $order);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($order, $toState, $normalized): Order {
                    $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                    $existing = OrderFulfillmentEvent::where('request_key', $normalized['request_key'])->first();
                    if ($existing) {
                        return $this->replay($existing, $normalized['request_hash'], $lockedOrder);
                    }
                    if ($lockedOrder->fulfillment_state === 'delivered') {
                        throw ValidationException::withMessages(['request_key' => 'Este pedido ya está entregado y no puede cancelarse ni entregarse de nuevo.']);
                    }
                    if ($lockedOrder->fulfillment_state === 'cancelled') {
                        throw ValidationException::withMessages(['request_key' => 'Este pedido está cancelado y no puede entregarse ni cancelarse de nuevo.']);
                    }
                    if (! in_array($lockedOrder->fulfillment_state, ['confirmed', 'in_preparation', 'ready'], true)) {
                        throw ValidationException::withMessages(['request_key' => 'Este pedido no está disponible para esta transición.']);
                    }

                    $lockedOrder->fulfillmentEvents()->create([
                        'from_state' => $lockedOrder->fulfillment_state,
                        'to_state' => $toState,
                        'request_key' => $normalized['request_key'],
                        'request_hash' => $normalized['request_hash'],
                        'effective_at' => now(),
                    ]);
                    $lockedOrder->update(['fulfillment_state' => $toState]);

                    return $lockedOrder;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                $existing = OrderFulfillmentEvent::where('request_key', $normalized['request_key'])->first();
                if ($existing) {
                    return $this->replay($existing, $normalized['request_hash'], $order);
                }
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('No se pudo actualizar la entrega.');
    }

    private function normalize(Order $order, string $toState, array $data): array
    {
        $requestKey = strtolower((string) ($data['request_key'] ?? ''));
        $payload = ['order_id' => $order->id, 'to_state' => $toState];

        return [
            ...$payload,
            'request_key' => $requestKey,
            'request_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
    }

    private function replay(OrderFulfillmentEvent $event, string $hash, Order $order): Order
    {
        if ($event->order_id !== $order->id || $event->request_hash !== $hash) {
            throw ValidationException::withMessages(['request_key' => 'Esta acción ya se registró con otros datos. Abre la pantalla de nuevo.']);
        }

        return $order->fresh(['lines', 'payments', 'fulfillmentEvents']);
    }
}
