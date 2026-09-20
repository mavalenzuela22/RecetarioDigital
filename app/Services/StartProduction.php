<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StartProduction
{
    public function start(string $from, string $to): int
    {
        return DB::transaction(function () use ($from, $to): int {
            $orders = Order::query()
                ->whereBetween('delivery_date', [$from, $to])
                ->where('fulfillment_state', 'confirmed')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();
            $transitions = 0;

            foreach ($orders as $order) {
                if ($order->delivery_date < $from || $order->delivery_date > $to || $order->fulfillment_state !== 'confirmed') {
                    continue;
                }

                $payload = [
                    'order_id' => $order->id,
                    'from_state' => $order->fulfillment_state,
                    'to_state' => 'in_preparation',
                ];
                $order->fulfillmentEvents()->create([
                    ...$payload,
                    'request_key' => (string) Str::uuid(),
                    'request_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
                    'effective_at' => now(),
                ]);
                $order->update(['fulfillment_state' => 'in_preparation']);
                $transitions++;
            }

            return $transitions;
        }, 3);
    }
}
