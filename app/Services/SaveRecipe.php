<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeVersion;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SaveRecipe
{
    public const UNITS = ['g' => 'g', 'kg' => 'g', 'ml' => 'ml', 'l' => 'ml', 'piece' => 'piece'];

    public static function quantity(mixed $value, string $field): int
    {
        return RecordIngredientPurchase::decimal($value, 3, $field);
    }

    public function save(array $data, ?Recipe $recipe = null): RecipeVersion
    {
        $normalized = $this->normalize($data, $recipe);
        $requestKey = strtolower($normalized['request_key']);
        $existing = RecipeVersion::where('request_key', $requestKey)->first();
        if ($existing) {
            return $this->replay($existing, $normalized['request_hash']);
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($normalized, $recipe): RecipeVersion {
                    $target = $recipe
                        ? Recipe::query()->lockForUpdate()->findOrFail($recipe->id)
                        : Recipe::create(['name' => $normalized['name']]);
                    $latest = $target->versions()->first();

                    if ($recipe && (string) ($normalized['base_version_id'] ?? '') !== (string) ($latest?->id ?? '')) {
                        throw new ConflictHttpException('Este registro cambió. Revisa la información antes de guardar.');
                    }
                    if (! $recipe && $normalized['base_version_id'] !== null) {
                        throw ValidationException::withMessages(['base_version_id' => 'Esta receta nueva no puede partir de una versión anterior.']);
                    }

                    $versionNumber = ((int) ($target->versions()->max('version_number') ?? 0)) + 1;
                    $ingredients = Ingredient::with('currentPurchase')
                        ->whereIn('id', array_column($normalized['ingredients'], 'ingredient_id'))
                        ->get()->keyBy('id');
                    $cost = $this->costForLines($normalized['ingredients'], $ingredients, (int) $normalized['expected_yield']);
                    $imagePath = $this->imagePath($normalized['image'], $latest?->image_path);
                    $version = $target->versions()->create([
                        'version_number' => $versionNumber,
                        'name' => $normalized['name'],
                        'expected_yield' => $normalized['expected_yield'],
                        'instructions' => $normalized['instructions'],
                        'notes' => $normalized['notes'],
                        'image_path' => $imagePath,
                        'snapshot_batch_cost_micros' => $cost['complete'] ? $cost['batch_cost_micros'] : null,
                        'snapshot_unit_cost_micros' => $cost['complete'] ? $cost['unit_cost_micros'] : null,
                        'request_key' => $normalized['request_key'],
                        'request_hash' => $normalized['request_hash'],
                    ]);
                    $version->lines()->createMany(array_map(
                        fn (array $line): array => [
                            'ingredient_id' => $line['ingredient_id'],
                            'position' => $line['position'],
                            'quantity_milli' => $line['quantity_milli'],
                            'unit' => $line['unit'],
                            'normalized_quantity_milli' => $line['normalized_quantity_milli'],
                            'snapshot_purchase_id' => $line['snapshot_purchase_id'],
                            'snapshot_unit_cost_micros' => $line['snapshot_unit_cost_micros'],
                            'snapshot_usage_cost_micros' => $line['snapshot_usage_cost_micros'],
                        ],
                        $cost['lines'],
                    ));

                    return $version->load('recipe');
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                $existing = RecipeVersion::where('request_key', $requestKey)->first();
                if ($existing) {
                    return $this->replay($existing, $normalized['request_hash']);
                }
                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('No se pudo guardar la receta.');
    }

    public function currentCost(RecipeVersion $version): array
    {
        $version->loadMissing('lines');
        $lines = $version->lines->map(fn ($line): array => [
            'ingredient_id' => (int) $line->ingredient_id,
            'position' => (int) $line->position,
            'quantity_milli' => (int) $line->quantity_milli,
            'unit' => $line->unit,
            'normalized_quantity_milli' => (int) $line->normalized_quantity_milli,
        ])->all();
        $ingredients = Ingredient::with('currentPurchase')
            ->whereIn('id', array_column($lines, 'ingredient_id'))
            ->get()->keyBy('id');

        return $this->costForLines($lines, $ingredients, (int) $version->expected_yield);
    }

    private function normalize(array $data, ?Recipe $recipe): array
    {
        $name = preg_replace('/\s+/u', ' ', trim((string) $data['name']));
        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Escribe el nombre de la receta.']);
        }
        $expectedYield = (string) $data['expected_yield'];
        if (! preg_match('/^[1-9][0-9]{0,8}$/', $expectedYield)) {
            throw ValidationException::withMessages(['expected_yield' => 'Escribe un número entero mayor que cero.']);
        }
        $ingredients = [];
        $seen = [];
        foreach (array_values($data['ingredients'] ?? []) as $position => $line) {
            $ingredientId = (int) ($line['ingredient_id'] ?? 0);
            $ingredient = Ingredient::find($ingredientId);
            if (! $ingredient) {
                throw ValidationException::withMessages(["ingredients.$position.ingredient_id" => 'Elige un ingrediente existente.']);
            }
            if (isset($seen[$ingredientId])) {
                throw ValidationException::withMessages(["ingredients.$position.ingredient_id" => 'Este ingrediente ya está en la receta. Edita la línea existente.']);
            }
            $seen[$ingredientId] = true;
            $unit = (string) ($line['unit'] ?? '');
            if (! isset(self::UNITS[$unit]) || self::UNITS[$unit] !== $ingredient->canonical_unit) {
                throw ValidationException::withMessages(["ingredients.$position.unit" => 'Elige una unidad compatible con este ingrediente.']);
            }
            $quantity = self::quantity($line['quantity'] ?? null, "ingredients.$position.quantity");
            $ingredients[] = [
                'ingredient_id' => $ingredientId,
                'position' => $position,
                'quantity_milli' => $quantity,
                'unit' => $unit,
                'normalized_quantity_milli' => $this->safeMultiply($quantity, in_array($unit, ['kg', 'l'], true) ? 1000 : 1, 'La cantidad de un ingrediente es demasiado grande.'),
            ];
        }
        if ($ingredients === []) {
            throw ValidationException::withMessages(['ingredients' => 'Agrega al menos un ingrediente.']);
        }

        $image = $data['image'] ?? null;
        $imageHash = $image instanceof UploadedFile ? hash_file('sha256', $image->getRealPath()) : null;
        $requestKey = strtolower((string) $data['request_key']);
        $hashPayload = [
            'recipe_id' => $recipe?->id,
            'base_version_id' => $data['base_version_id'] ?? null,
            'name' => $name,
            'expected_yield' => $expectedYield,
            'instructions' => $data['instructions'] ?? null,
            'notes' => $data['notes'] ?? null,
            'ingredients' => $ingredients,
            'image_hash' => $imageHash,
        ];

        return [
            'name' => $name,
            'expected_yield' => (int) $expectedYield,
            'instructions' => $data['instructions'] ?? null,
            'notes' => $data['notes'] ?? null,
            'base_version_id' => isset($data['base_version_id']) && $data['base_version_id'] !== '' ? (int) $data['base_version_id'] : null,
            'ingredients' => $ingredients,
            'image' => $image,
            'request_key' => $requestKey,
            'request_hash' => hash('sha256', json_encode($hashPayload, JSON_THROW_ON_ERROR)),
        ];
    }

    private function costForLines(array $lines, $ingredients, int $expectedYield): array
    {
        $complete = true;
        $missing = [];
        $batch = 0;
        $costLines = [];
        foreach ($lines as $line) {
            $ingredient = $ingredients->get($line['ingredient_id']);
            $purchase = $ingredient?->currentPurchase;
            $usage = null;
            $unitCost = null;
            $purchaseId = null;
            if ($purchase) {
                $purchaseId = $purchase->id;
                $unitCost = (int) $purchase->normalized_unit_cost_micros;
                $usage = $this->multiplyDivideRound($unitCost, $line['normalized_quantity_milli'], 1000, 'El costo de la receta excede el límite exacto permitido.');
                $batch = $this->safeAdd($batch, $usage, 'El costo total de la receta excede el límite exacto permitido.');
            } else {
                $complete = false;
                $missing[] = $ingredient?->name ?? 'Ingrediente';
            }
            $costLines[] = $line + [
                'snapshot_purchase_id' => $purchaseId,
                'snapshot_unit_cost_micros' => $unitCost,
                'snapshot_usage_cost_micros' => $usage,
            ];
        }

        return [
            'complete' => $complete,
            'missing' => $missing,
            'batch_cost_micros' => $complete ? (string) $batch : null,
            'unit_cost_micros' => $complete ? (string) $this->divideRound($batch, $expectedYield, 'El costo por unidad excede el límite exacto permitido.') : null,
            'lines' => array_map(fn (array $line): array => array_merge($line, [
                'snapshot_unit_cost_micros' => $line['snapshot_unit_cost_micros'] === null ? null : (string) $line['snapshot_unit_cost_micros'],
                'snapshot_usage_cost_micros' => $line['snapshot_usage_cost_micros'] === null ? null : (string) $line['snapshot_usage_cost_micros'],
            ]), $costLines),
        ];
    }

    private function replay(RecipeVersion $existing, string $hash): RecipeVersion
    {
        if ($existing->request_hash !== $hash) {
            throw ValidationException::withMessages(['request_key' => 'Esta receta ya se guardó con otros datos. Abre una nueva captura para guardar otra versión.']);
        }

        return $existing->load('recipe');
    }

    private function imagePath(?UploadedFile $image, ?string $previousPath): ?string
    {
        return $image?->store('recipes', 'local') ?? $previousPath;
    }

    private function safeMultiply(int $left, int $right, string $message): int
    {
        if ($left < 0 || $right < 0 || ($right !== 0 && intdiv(PHP_INT_MAX, $right) < $left)) {
            throw ValidationException::withMessages(['ingredients' => $message]);
        }

        return $left * $right;
    }

    private function safeAdd(int $left, int $right, string $message): int
    {
        if ($left < 0 || $right < 0 || $left > PHP_INT_MAX - $right) {
            throw ValidationException::withMessages(['ingredients' => $message]);
        }

        return $left + $right;
    }

    private function multiplyDivideRound(int $left, int $right, int $divisor, string $message): int
    {
        $leftQuotient = intdiv($left, $divisor);
        $leftRemainder = $left % $divisor;
        $base = $this->safeMultiply($leftQuotient, $right, $message);
        $rightQuotient = intdiv($right, $divisor);
        $rightRemainder = $right % $divisor;
        $base = $this->safeAdd($base, $this->safeMultiply($rightQuotient, $leftRemainder, $message), $message);
        $smallProduct = $rightRemainder * $leftRemainder;
        $result = $this->safeAdd($base, intdiv($smallProduct, $divisor), $message);
        if (($smallProduct % $divisor) * 2 >= $divisor) {
            $result = $this->safeAdd($result, 1, $message);
        }

        return $result;
    }

    private function divideRound(int $numerator, int $denominator, string $message): int
    {
        if ($denominator <= 0) {
            throw ValidationException::withMessages(['expected_yield' => 'Escribe un número entero mayor que cero.']);
        }
        $quotient = intdiv($numerator, $denominator);
        $remainder = $numerator % $denominator;
        if ($remainder * 2 >= $denominator) {
            return $this->safeAdd($quotient, 1, $message);
        }

        return $quotient;
    }
}
