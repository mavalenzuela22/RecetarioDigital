<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductCostProfile;
use App\Models\Recipe;
use App\Services\ProductCosting;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SaveProductProfile
{
    public function create(array $data): Product
    {
        $normalized = $this->normalize($data, null);
        $existing = ProductCostProfile::where('request_key', $normalized['request_key'])->first();
        if ($existing) {
            return $this->replay($existing, $normalized['request_hash']);
        }

        try {
            return DB::transaction(function () use ($normalized): Product {
                $product = Product::create([
                    'recipe_id' => $normalized['recipe_id'],
                    'sale_unit' => 'piece',
                    'active' => $normalized['active'],
                ]);

                $this->writeProfile($product, $normalized, 1);

                return $product->load('recipe');
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            if ($existing = ProductCostProfile::where('request_key', $normalized['request_key'])->first()) {
                return $this->replay($existing, $normalized['request_hash']);
            }
            if ($product = Product::where('recipe_id', $normalized['recipe_id'])->first()) {
                throw ValidationException::withMessages(['recipe_id' => 'Esta receta ya tiene un producto. Abre el producto existente para cambiar sus costos.']);
            }
            throw $exception;
        }
    }

    public function save(array $data, Product $product): Product
    {
        $normalized = $this->normalize($data, $product);
        $existing = ProductCostProfile::where('request_key', $normalized['request_key'])->first();
        if ($existing) {
            return $this->replay($existing, $normalized['request_hash']);
        }

        return DB::transaction(function () use ($normalized, $product): Product {
            $target = Product::query()->lockForUpdate()->findOrFail($product->id);
            $latest = $target->latestProfile()->first();
            if ((string) ($normalized['base_profile_id'] ?? '') !== (string) ($latest?->id ?? '')) {
                throw new ConflictHttpException('Esta configuración cambió. Revisa la información antes de guardar.');
            }
            $version = ((int) ($target->costProfiles()->max('version_number') ?? 0)) + 1;
            $target->update(['active' => $normalized['active']]);
            $this->writeProfile($target, $normalized, $version);

            return $target->load('recipe');
        }, 3);
    }

    private function writeProfile(Product $product, array $normalized, int $version): ProductCostProfile
    {
        $profile = $product->costProfiles()->create([
            'version_number' => $version,
            'reference_order_quantity' => $normalized['reference_order_quantity'],
            'request_key' => $normalized['request_key'],
            'request_hash' => $normalized['request_hash'],
        ]);
        $profile->components()->createMany($normalized['components']);

        return $profile;
    }

    private function normalize(array $data, ?Product $product): array
    {
        if ($product) {
            $product->loadMissing('recipe.latestVersion');
            $recipe = $product->recipe;
        } else {
            $recipe = Recipe::with('latestVersion')->find($data['recipe_id'] ?? null);
        }
        if (! $recipe || ! $recipe->latestVersion) {
            throw ValidationException::withMessages(['recipe_id' => 'Elige una receta con una versión guardada.']);
        }
        if ($product && (int) $product->recipe_id !== (int) $recipe->id) {
            throw ValidationException::withMessages(['recipe_id' => 'La receta del producto no puede cambiar.']);
        }

        $components = [];
        foreach (array_values($data['components'] ?? []) as $position => $component) {
            $concept = preg_replace('/\s+/u', ' ', trim((string) ($component['concept'] ?? '')));
            if ($concept === '' || mb_strlen($concept) > 120) {
                throw ValidationException::withMessages(["components.$position.concept" => 'Escribe un concepto de hasta 120 caracteres.']);
            }
            $allocation = (string) ($component['allocation'] ?? '');
            if (! in_array($allocation, ['batch', 'unit', 'order'], true)) {
                throw ValidationException::withMessages(["components.$position.allocation" => 'Elige cómo se asigna este costo.']);
            }
            $components[] = [
                'position' => $position,
                'concept' => $concept,
                'amount_minor' => ProductCosting::minor($component['amount'] ?? null, "components.$position.amount"),
                'allocation' => $allocation,
            ];
        }
        $reference = (string) ($data['reference_order_quantity'] ?? '');
        if ($reference === '') {
            $reference = (string) $recipe->latestVersion->expected_yield;
        }
        if (! preg_match('/^[1-9][0-9]{0,8}$/', $reference)) {
            throw ValidationException::withMessages(['reference_order_quantity' => 'Escribe una cantidad de referencia entera mayor que cero.']);
        }
        $requestKey = strtolower((string) ($data['request_key'] ?? ''));
        $payload = [
            'product_id' => $product?->id,
            'recipe_id' => $recipe->id,
            'base_profile_id' => $data['base_profile_id'] ?? null,
            'active' => (bool) ($data['active'] ?? true),
            'reference_order_quantity' => $reference,
            'components' => $components,
        ];

        return [
            ...$payload,
            'active' => (bool) $payload['active'],
            'request_key' => $requestKey,
            'request_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
        ];
    }

    private function replay(ProductCostProfile $existing, string $hash): Product
    {
        if ($existing->request_hash !== $hash) {
            throw ValidationException::withMessages(['request_key' => 'Esta configuración ya se guardó con otros datos. Abre una nueva captura.']);
        }

        return $existing->product()->with('recipe')->firstOrFail();
    }
}
