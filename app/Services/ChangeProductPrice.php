<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChangeProductPrice
{
    public function __construct(private readonly ProductCosting $costing) {}

    public function change(Product $product, array $data): ProductPrice
    {
        $mode = (string) ($data['mode'] ?? '');
        $scenario = isset($data['scenario']) ? (string) $data['scenario'] : null;
        $cost = $this->costing->current($product);
        if ($mode === 'scenario') {
            $selected = collect($cost['scenarios'])->firstWhere('multiplier', $scenario);
            if (! $cost['complete'] || ! $scenario || ! $selected) {
                throw ValidationException::withMessages(['scenario' => 'Este escenario no está disponible porque falta información para calcular el costo.']);
            }
            $priceMinor = (int) $selected['suggested_price_minor'];
        } elseif ($mode === 'manual') {
            $priceMinor = ProductCosting::minor($data['price'] ?? null, 'price', true);
        } else {
            throw ValidationException::withMessages(['mode' => 'Elige un precio válido.']);
        }
        if ($priceMinor <= 0) {
            throw ValidationException::withMessages(['price' => 'El precio debe ser mayor que $0.']);
        }
        if (! ($data['confirmed'] ?? false)) {
            throw ValidationException::withMessages(['confirmed' => 'Confirma que el precio se aplicará a nuevos pedidos.']);
        }

        $requestKey = strtolower((string) ($data['request_key'] ?? ''));
        $payload = [
            'product_id' => $product->id,
            'mode' => $mode,
            'scenario' => $scenario,
            'price_minor' => (string) $priceMinor,
            'confirmed' => true,
        ];
        $hash = hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        $existing = ProductPrice::where('request_key', $requestKey)->first();
        if ($existing) {
            return $this->replay($existing, $hash);
        }

        try {
            return DB::transaction(fn (): ProductPrice => $product->prices()->create([
                'price_minor' => $priceMinor,
                'effective_at' => now(),
                'request_key' => $requestKey,
                'request_hash' => $hash,
            ]), 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = ProductPrice::where('request_key', $requestKey)->first();
            if ($existing) {
                return $this->replay($existing, $hash);
            }
            throw $exception;
        }
    }

    private function replay(ProductPrice $existing, string $hash): ProductPrice
    {
        if ($existing->request_hash !== $hash) {
            throw ValidationException::withMessages(['request_key' => 'Este cambio de precio ya se registró con otros datos. Abre una nueva captura.']);
        }

        return $existing;
    }
}
