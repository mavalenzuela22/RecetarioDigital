<?php

namespace App\Services;

use App\Models\IngredientPurchase;
use App\Models\Product;
use App\Models\ProductCostProfile;
use App\Models\ProductPrice;
use App\Models\RecipeVersion;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class ProductHistoryComparison
{
    public const MISSING_REFERENCE = 'No hay una referencia económica para esa fecha. Elige otra fecha.';

    public const DIFFERENT_RECIPE = 'La receta o el rendimiento cambiaron. Compara el desglose de cada versión.';

    public const DISCLAIMER = 'Compara la economía configurada del producto. No recalcula pedidos históricos.';

    public function compare(Product $product, string $asOf, string $until): array
    {
        $asOfDate = $this->date($asOf, 'asOf');
        $untilDate = $this->date($until, 'until');
        if ($untilDate->lt($asOfDate)) {
            return [
                'error' => 'La fecha final debe ser igual o posterior a la inicial.',
                'before' => null,
                'now' => null,
                'cost_delta_micros' => null,
                'cost_delta_abs_micros' => null,
                'cost_delta_percent' => null,
                'recipe_changed' => null,
                'profile_changed' => null,
                'drivers' => null,
            ];
        }

        $before = $this->side($product, $asOfDate);
        $now = $this->side($product, $untilDate);
        $comparison = [
            'error' => null,
            'before' => $before,
            'now' => $now,
            'cost_delta_micros' => null,
            'cost_delta_abs_micros' => null,
            'cost_delta_percent' => null,
            'recipe_changed' => null,
            'profile_changed' => null,
            'drivers' => null,
        ];

        if (! $before['available'] || ! $now['available']) {
            return $comparison;
        }

        $comparison['recipe_changed'] = ! $this->sameRecipeEconomics($before['recipe'], $now['recipe']);
        $comparison['profile_changed'] = $this->profileSignature($before['profile']) !== $this->profileSignature($now['profile']);

        if (! $before['complete'] || ! $now['complete']) {
            return $comparison;
        }

        $beforeCost = (int) $before['economics']['unit_cost_micros'];
        $nowCost = (int) $now['economics']['unit_cost_micros'];
        $delta = ProductCosting::safeAdd($nowCost, -$beforeCost, 'La variación de costo excede el límite exacto permitido.');
        $comparison['cost_delta_micros'] = (string) $delta;
        $comparison['cost_delta_abs_micros'] = (string) abs($delta);
        $comparison['cost_delta_percent'] = $beforeCost === 0
            ? null
            : $this->percentage($delta, $beforeCost, 'La variación porcentual excede el límite exacto permitido.');

        if (! $comparison['recipe_changed']) {
            $comparison['drivers'] = $this->drivers($before, $now);
        }

        return $comparison;
    }

    private function side(Product $product, CarbonImmutable $date): array
    {
        $dateString = $date->format('Y-m-d');
        $close = $date->endOfDay();
        $recipe = RecipeVersion::query()
            ->where('recipe_id', $product->recipe_id)
            ->where('created_at', '<=', $close)
            ->orderByDesc('created_at')
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->first();
        $profile = ProductCostProfile::query()
            ->with('components')
            ->where('product_id', $product->id)
            ->where('created_at', '<=', $close)
            ->orderByDesc('created_at')
            ->orderByDesc('version_number')
            ->orderByDesc('id')
            ->first();
        $price = ProductPrice::query()
            ->where('product_id', $product->id)
            ->where('effective_at', '<=', $close)
            ->orderByDesc('effective_at')
            ->orderByDesc('id')
            ->first();

        $side = [
            'date' => $dateString,
            'available' => (bool) ($recipe && $profile && $price),
            'complete' => false,
            'message' => null,
            'recipe' => null,
            'profile' => null,
            'price' => null,
            'economics' => [
                'unit_cost_micros' => null,
                'sale_price_minor' => $price ? (string) $price->price_minor : null,
                'profit_per_unit_micros' => null,
                'margin_percent' => null,
            ],
            'provenance' => null,
        ];

        if (! $recipe || ! $profile || ! $price) {
            $side['message'] = self::MISSING_REFERENCE;

            return $side;
        }

        $recipe->load(['lines.ingredient']);
        $recipeLines = [];
        $batchCost = 0;
        $complete = true;
        foreach ($recipe->lines as $line) {
            $purchase = IngredientPurchase::query()
                ->where('ingredient_id', $line->ingredient_id)
                ->whereDate('purchased_on', '<=', $dateString)
                ->orderByDesc('purchased_on')
                ->orderByDesc('id')
                ->first();
            $usage = null;
            $unitCost = null;
            if ($purchase) {
                $unitCost = (int) $purchase->normalized_unit_cost_micros;
                $usage = ProductCosting::multiplyDivideRound(
                    $unitCost,
                    (int) $line->normalized_quantity_milli,
                    1000,
                    'El costo histórico de la receta excede el límite exacto permitido.',
                );
                $batchCost = ProductCosting::safeAdd(
                    $batchCost,
                    $usage,
                    'El costo histórico de la receta excede el límite exacto permitido.',
                );
            } else {
                $complete = false;
            }
            $purchaseDate = $purchase?->getRawOriginal('purchased_on');
            $recipeLines[] = [
                'ingredient_id' => (int) $line->ingredient_id,
                'ingredient_name' => $line->ingredient?->name,
                'normalized_quantity_milli' => (string) $line->normalized_quantity_milli,
                'purchase_date' => $purchaseDate,
                'purchase_id' => $purchase?->id,
                'unit_cost_micros' => $unitCost === null ? null : (string) $unitCost,
                'usage_cost_micros' => $usage === null ? null : (string) $usage,
            ];
        }

        $recipeUnitCost = $complete
            ? ProductCosting::multiplyDivideRound($batchCost, 1, (int) $recipe->expected_yield, 'El costo histórico por unidad excede el límite exacto permitido.')
            : null;
        $unitCost = $recipeUnitCost;
        if ($complete) {
            foreach ($profile->components as $component) {
                $amountMicros = ProductCosting::minorToMicros(
                    (int) $component->amount_minor,
                    'El costo histórico adicional excede el límite exacto permitido.',
                );
                $denominator = match ($component->allocation) {
                    'batch' => (int) $recipe->expected_yield,
                    'order' => (int) $profile->reference_order_quantity,
                    default => 1,
                };
                $perUnit = $component->allocation === 'unit'
                    ? $amountMicros
                    : ProductCosting::multiplyDivideRound($amountMicros, 1, $denominator, 'El costo histórico adicional excede el límite exacto permitido.');
                $unitCost = ProductCosting::safeAdd(
                    (int) $unitCost,
                    $perUnit,
                    'El costo histórico del producto excede el límite exacto permitido.',
                );
            }
        }

        $recipePayload = [
            'id' => $recipe->id,
            'version_number' => (string) $recipe->version_number,
            'expected_yield' => (string) $recipe->expected_yield,
            'lines' => $recipeLines,
        ];
        $profilePayload = [
            'id' => $profile->id,
            'version_number' => (string) $profile->version_number,
            'reference_order_quantity' => (string) $profile->reference_order_quantity,
            'components' => $profile->components->map(fn ($component): array => [
                'position' => (string) $component->position,
                'concept' => $component->concept,
                'amount_minor' => (string) $component->amount_minor,
                'allocation' => $component->allocation,
            ])->values()->all(),
        ];
        $priceTimestamp = $price->effective_at->copy()->setTimezone(config('app.timezone'));
        $side['recipe'] = $recipePayload;
        $side['profile'] = $profilePayload;
        $side['price'] = [
            'id' => $price->id,
            'price_minor' => (string) $price->price_minor,
            'effective_at' => $priceTimestamp->toIso8601String(),
            'effective_date' => $priceTimestamp->format('Y-m-d'),
        ];
        $side['complete'] = $complete;
        $side['provenance'] = [
            'recipe' => 'Versión '.$recipe->version_number.' · rendimiento '.$recipe->expected_yield.' piezas',
            'profile' => 'Perfil '.$profile->version_number.' · pedido de referencia '.$profile->reference_order_quantity.' piezas',
            'price' => 'Precio vigente desde '.$priceTimestamp->format('Y-m-d H:i'),
            'ingredients' => array_map(fn (array $line): array => [
                'ingredient_name' => $line['ingredient_name'],
                'purchase_date' => $line['purchase_date'],
            ], $recipeLines),
        ];

        if (! $complete) {
            return $side;
        }

        $salePriceMicros = ProductCosting::minorToMicros((int) $price->price_minor, 'El precio histórico excede el límite exacto permitido.');
        $profit = ProductCosting::safeAdd($salePriceMicros, -((int) $unitCost), 'La ganancia histórica excede el límite exacto permitido.');
        $side['economics'] = [
            'unit_cost_micros' => (string) $unitCost,
            'sale_price_minor' => (string) $price->price_minor,
            'profit_per_unit_micros' => (string) $profit,
            'margin_percent' => $salePriceMicros === 0 ? null : $this->percentage($profit, $salePriceMicros, 'El margen histórico excede el límite exacto permitido.'),
        ];

        return $side;
    }

    private function drivers(array $before, array $now): array
    {
        $beforeLines = collect($before['recipe']['lines'])->keyBy('ingredient_id');
        $nowLines = collect($now['recipe']['lines'])->keyBy('ingredient_id');
        $drivers = [];
        foreach ($beforeLines as $ingredientId => $beforeLine) {
            $nowLine = $nowLines->get($ingredientId);
            if (! $nowLine) {
                continue;
            }
            $beforeContribution = ProductCosting::multiplyDivideRound((int) $beforeLine['usage_cost_micros'], 1, (int) $before['recipe']['expected_yield'], 'El driver histórico excede el límite exacto permitido.');
            $nowContribution = ProductCosting::multiplyDivideRound((int) $nowLine['usage_cost_micros'], 1, (int) $now['recipe']['expected_yield'], 'El driver histórico excede el límite exacto permitido.');
            $delta = ProductCosting::safeAdd($nowContribution, -$beforeContribution, 'El driver histórico excede el límite exacto permitido.');
            if ($delta === 0) {
                continue;
            }
            $drivers[] = [
                'ingredient_id' => (int) $ingredientId,
                'ingredient_name' => $nowLine['ingredient_name'] ?? $beforeLine['ingredient_name'],
                'delta_micros' => (string) $delta,
                'before_micros' => (string) $beforeContribution,
                'now_micros' => (string) $nowContribution,
                'before_purchase_date' => $beforeLine['purchase_date'],
                'now_purchase_date' => $nowLine['purchase_date'],
            ];
        }
        usort($drivers, static function (array $left, array $right): int {
            $magnitude = abs((int) $right['delta_micros']) <=> abs((int) $left['delta_micros']);

            return $magnitude !== 0 ? $magnitude : $left['ingredient_id'] <=> $right['ingredient_id'];
        });

        return $drivers;
    }

    private function sameRecipeEconomics(array $before, array $now): bool
    {
        $beforeLines = collect($before['lines'])->mapWithKeys(fn (array $line): array => [(string) $line['ingredient_id'] => (string) $line['normalized_quantity_milli']])->sortKeys()->all();
        $nowLines = collect($now['lines'])->mapWithKeys(fn (array $line): array => [(string) $line['ingredient_id'] => (string) $line['normalized_quantity_milli']])->sortKeys()->all();

        return (string) $before['expected_yield'] === (string) $now['expected_yield'] && $beforeLines === $nowLines;
    }

    private function profileSignature(?array $profile): ?array
    {
        if ($profile === null) {
            return null;
        }

        return [
            'reference_order_quantity' => (string) $profile['reference_order_quantity'],
            'components' => array_map(static fn (array $component): array => [
                'position' => (string) $component['position'],
                'concept' => $component['concept'],
                'amount_minor' => (string) $component['amount_minor'],
                'allocation' => $component['allocation'],
            ], $profile['components']),
        ];
    }

    private function percentage(int $numerator, int $denominator, string $message): string
    {
        $negative = $numerator < 0;
        $absolute = ProductCosting::safeMultiply(abs($numerator), 1000, $message);
        $rounded = ProductCosting::multiplyDivideRound($absolute, 1, $denominator, $message);
        $value = intdiv($rounded, 10).'.'.($rounded % 10);

        return $negative ? '-'.$value : $value;
    }

    private function date(string $value, string $field): CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, config('app.timezone'));
        if (! $date || $date->format('Y-m-d') !== $value) {
            throw ValidationException::withMessages([$field => 'Escribe una fecha válida.']);
        }

        return $date;
    }
}
