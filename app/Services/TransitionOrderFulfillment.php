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
        if (! in_array($toState, ['delivered', 'cancelled', 'ready'], true)) {
            throw new \InvalidArgumentException('Transición de entrega no autorizada.');
        }
        $normalized = $this->normalize($order, $toState, $data);
        $existing = OrderFulfillmentEvent::where('request_key', $normalized['request_key'])->first();
        if ($existing) {
            return $this->replay($existing, $normalized, $order);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($order, $toState, $normalized): Order {
                    $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
                    $existing = OrderFulfillmentEvent::where('request_key', $normalized['request_key'])->first();
                    if ($existing) {
                        return $this->replay($existing, $normalized, $lockedOrder);
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
                    if ($toState === 'ready' && $lockedOrder->fulfillment_state !== 'in_preparation') {
                        throw ValidationException::withMessages(['request_key' => 'Solo un pedido en preparación puede marcarse como listo.']);
                    }

                    $payload = ['order_id' => $lockedOrder->id, 'from_state' => $lockedOrder->fulfillment_state, 'to_state' => $toState];
                    $lockedOrder->fulfillmentEvents()->create([
                        'from_state' => $lockedOrder->fulfillment_state,
                        'to_state' => $toState,
                        'request_key' => $normalized['request_key'],
                        'request_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                        'effective_at' => now(),
                    ]);
                    $lockedOrder->update(['fulfillment_state' => $toState]);

                    return $lockedOrder;
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                $existing = OrderFulfillmentEvent::where('request_key', $normalized['request_key'])->first();
                if ($existing) {
                    return $this->replay($existing, $normalized, $order);
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
        return [
            'order_id' => $order->id,
            'to_state' => $toState,
            'request_key' => strtolower((string) ($data['request_key'] ?? '')),
        ];
    }

    private function replay(OrderFulfillmentEvent $event, array $normalized, Order $order): Order
    {
        $payload = [
            'order_id' => $event->order_id,
            'from_state' => $event->from_state,
            'to_state' => $event->to_state,
        ];
        $recordedHash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));

        if ($event->order_id !== $normalized['order_id'] || $event->to_state !== $normalized['to_state'] || $event->request_hash !== $recordedHash) {
            throw ValidationException::withMessages(['request_key' => 'Esta acción ya se registró con otros datos. Abre la pantalla de nuevo.']);
        }

        return $order->fresh(['lines', 'payments', 'fulfillmentEvents']);
    }
}
