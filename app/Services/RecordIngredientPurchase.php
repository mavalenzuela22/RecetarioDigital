<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\IngredientPurchase;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordIngredientPurchase
{
    public const UNITS = ['g' => 'g', 'kg' => 'g', 'ml' => 'ml', 'l' => 'ml', 'piece' => 'piece'];

    public static function decimal(mixed $value, int $scale, string $field): int
    {
        // Nine integral digits bound every multiplication below PHP's signed 64-bit maximum.
        if (! is_string($value) || ! preg_match('/\A[0-9]{1,9}(?:[.,][0-9]{1,'.$scale.'})?\z/D', $value)) {
            throw ValidationException::withMessages([$field => $scale === 2
                ? 'Escribe el importe sin separador de miles; por ejemplo, 1234.00. Máximo 9 enteros y 2 decimales.'
                : 'Usa hasta 9 enteros y 3 decimales, sin separador de miles; por ejemplo, 1.250.']);
        }
        $parts = explode('.', str_replace(',', '.', $value));
        $result = (int) $parts[0] * (10 ** $scale) + (int) str_pad($parts[1] ?? '', $scale, '0');
        if ($result === 0) {
            throw ValidationException::withMessages([$field => $scale === 2
                ? 'Escribe un total mayor que $0.' : 'Escribe una cantidad mayor que cero.']);
        }

        return $result;
    }

    public static function facts(array $data): array
    {
        $paid = self::decimal($data['total_paid'], 2, 'total_paid');
        $quantity = self::decimal($data['purchase_quantity'], 3, 'purchase_quantity');
        $normalized = $quantity * (in_array($data['purchase_unit'], ['kg', 'l'], true) ? 1000 : 1);
        // MXN/unit * 1e6 = (cents * 1e7) / canonical thousandths.
        // Round half up to the nearest micro using quotient/remainder, never floats.
        $numerator = $paid * 10000000;
        $cost = intdiv($numerator, $normalized);
        if (($numerator % $normalized) * 2 >= $normalized) {
            $cost++;
        }

        return [
            'total_paid_minor' => $paid,
            'purchase_quantity_milli' => $quantity,
            'normalized_quantity_milli' => $normalized,
            'normalized_unit_cost_micros' => $cost,
        ];
    }

    public static function nameKey(string $name): string
    {
        return hash('sha256', mb_strtolower(preg_replace('/\s+/u', ' ', trim($name)), 'UTF-8'));
    }

    public function record(array $data): IngredientPurchase
    {
        $name = preg_replace('/\s+/u', ' ', trim($data['ingredient_name']));
        $nameKey = self::nameKey($name);
        $facts = self::facts($data) + [
            'purchase_unit' => $data['purchase_unit'],
            'presentation' => $data['presentation'],
            'purchased_on' => $data['purchased_on'],
            'store' => $data['store'] ?? null,
            'note' => $data['note'] ?? null,
            'request_key' => strtolower($data['request_key']),
        ];
        if ($existing = IngredientPurchase::where('request_key', $facts['request_key'])->first()) {
            return $this->replay($existing, $facts, $nameKey);
        }
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($name, $nameKey, $facts): IngredientPurchase {
                    $ingredient = Ingredient::firstOrCreate(['name_key' => $nameKey], [
                        'name' => $name, 'canonical_unit' => self::UNITS[$facts['purchase_unit']],
                    ]);
                    if ($ingredient->canonical_unit !== self::UNITS[$facts['purchase_unit']]) {
                        throw ValidationException::withMessages([
                            'purchase_unit' => 'Este ingrediente se mide en '.$ingredient->canonical_unit.'. Elige una unidad compatible.',
                        ]);
                    }

                    return $ingredient->purchases()->create($facts);
                }, 3);
            } catch (UniqueConstraintViolationException $exception) {
                // Retry outside the rolled-back transaction: MySQL REPEATABLE READ may
                // hide a concurrently created ingredient until we obtain a fresh snapshot.
                $existing = IngredientPurchase::where('request_key', $facts['request_key'])->first();
                if ($existing) {
                    return $this->replay($existing, $facts, $nameKey);
                }

                if ($attempt === 2) {
                    throw $exception;
                }
            }
        }

        throw new \LogicException('No se pudo registrar la compra.');
    }

    private function replay(IngredientPurchase $existing, array $facts, string $nameKey): IngredientPurchase
    {
        foreach ($facts as $field => $value) {
            if ((string) $existing->{$field} !== (string) $value) {
                throw ValidationException::withMessages(['request_key' => 'Esta compra ya se registró con otros datos. Abre una nueva compra para registrar otra.']);
            }
        }
        if ($existing->ingredient->name_key !== $nameKey) {
            throw ValidationException::withMessages(['request_key' => 'Esta compra ya se registró para otro ingrediente. Abre una nueva compra.']);
        }

        return $existing;
    }
}
