<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class ProductCosting
{
    public const SCENARIOS = [
        '2' => [2, 1],
        '2.5' => [5, 2],
        '3' => [3, 1],
        '3.5' => [7, 2],
    ];

    public function __construct(private readonly SaveRecipe $recipeCosting) {}

    public static function minor(mixed $value, string $field, bool $positive = false): int
    {
        if (! is_string($value) || ! preg_match('/\A[0-9]{1,13}(?:[.,][0-9]{1,2})?\z/D', $value)) {
            throw ValidationException::withMessages([$field => 'Escribe un importe válido, sin separador de miles y con hasta 2 decimales.']);
        }
        $parts = explode('.', str_replace(',', '.', $value));
        $minor = self::safeMultiply((int) $parts[0], 100, 'El importe excede el límite exacto permitido.');
        $minor = self::safeAdd($minor, (int) str_pad($parts[1] ?? '', 2, '0'), 'El importe excede el límite exacto permitido.');
        if ($positive && $minor === 0) {
            throw ValidationException::withMessages([$field => 'Escribe un precio mayor que $0.']);
        }

        return $minor;
    }

    public function current(Product $product): array
    {
        $product->loadMissing(['recipe.latestVersion', 'latestProfile.components', 'latestPrice']);
        $profile = $product->latestProfile;
        $version = $product->recipe?->latestVersion;
        $recipeCost = $version ? $this->recipeCosting->currentCost($version) : ['complete' => false, 'missing' => ['receta']];
        $profilePayload = $this->profilePayload($profile);
        $base = [
            'complete' => false,
            'unit_cost_micros' => null,
            'recipe' => [
                'batch_cost_micros' => $recipeCost['batch_cost_micros'] ?? null,
                'unit_cost_micros' => $recipeCost['unit_cost_micros'] ?? null,
                'expected_yield' => $version?->expected_yield,
            ],
            'profile' => $profilePayload,
            'missing' => $recipeCost['missing'] ?? ['receta'],
            'current_price_minor' => $product->latestPrice?->price_minor,
            'current_price_metrics' => null,
            'scenarios' => [],
        ];
        if (! $profile || ! $version || ! ($recipeCost['complete'] ?? false)) {
            return $base;
        }

        $yield = (int) $version->expected_yield;
        $reference = (int) $profile->reference_order_quantity;
        $batch = [];
        $unit = [];
        $order = [];
        $total = (int) $recipeCost['unit_cost_micros'];
        foreach ($profile->components as $component) {
            $amountMinor = (int) $component->amount_minor;
            $amountMicros = self::minorToMicros($amountMinor, 'El costo adicional excede el límite exacto permitido.');
            $denominator = match ($component->allocation) {
                'batch' => $yield,
                'order' => $reference,
                default => 1,
            };
            $perUnit = $component->allocation === 'unit'
                ? $amountMicros
                : self::multiplyDivideRound($amountMicros, 1, $denominator, 'El costo adicional excede el límite exacto permitido.');
            $total = self::safeAdd($total, $perUnit, 'El costo atribuible del producto excede el límite exacto permitido.');
            $row = [
                'concept' => $component->concept,
                'amount_minor' => (string) $amountMinor,
                'allocation' => $component->allocation,
                'per_unit_micros' => (string) $perUnit,
                'denominator' => (string) $denominator,
            ];
            match ($component->allocation) {
                'batch' => $batch[] = $row,
                'order' => $order[] = $row,
                default => $unit[] = $row,
            };
        }

        $breakdown = [
            'recipe' => [
                'batch_cost_micros' => $recipeCost['batch_cost_micros'],
                'unit_cost_micros' => $recipeCost['unit_cost_micros'],
                'yield' => (string) $yield,
            ],
            'batch' => $batch,
            'unit' => $unit,
            'order' => $order,
        ];

        $base['complete'] = true;
        $base['unit_cost_micros'] = (string) $total;
        $base['breakdown'] = $breakdown;
        if ($product->latestPrice) {
            $base['current_price_metrics'] = $this->priceMetrics($total, $yield, (int) $product->latestPrice->price_minor);
        }
        $base['scenarios'] = array_map(fn (array $ratio, string $multiplier): array => $this->scenario($total, $yield, $multiplier, $ratio), self::SCENARIOS, array_keys(self::SCENARIOS));

        return $base;
    }

    public static function safeAdd(int $left, int $right, string $message): int
    {
        if ($right > 0 && $left > PHP_INT_MAX - $right) {
            throw ValidationException::withMessages(['components' => $message]);
        }
        if ($right < 0 && $left < PHP_INT_MIN - $right) {
            throw ValidationException::withMessages(['price' => $message]);
        }

        return $left + $right;
    }

    public static function safeMultiply(int $left, int $right, string $message): int
    {
        if ($left === 0 || $right === 0) {
            return 0;
        }
        $negative = ($left < 0) xor ($right < 0);
        $a = abs($left);
        $b = abs($right);
        if ($a > intdiv(PHP_INT_MAX, $b)) {
            throw ValidationException::withMessages(['components' => $message]);
        }
        $result = $a * $b;

        return $negative ? -$result : $result;
    }

    public static function minorToMicros(int $minor, string $message): int
    {
        return self::safeMultiply($minor, 10000, $message);
    }

    public static function multiplyDivideRound(int $left, int $right, int $divisor, string $message): int
    {
        if ($left < 0 || $right < 0 || $divisor <= 0) {
            throw ValidationException::withMessages(['components' => $message]);
        }
        $leftQuotient = intdiv($left, $divisor);
        $leftRemainder = $left % $divisor;
        $base = self::safeMultiply($leftQuotient, $right, $message);
        $rightQuotient = intdiv($right, $divisor);
        $rightRemainder = $right % $divisor;
        $base = self::safeAdd($base, self::safeMultiply($rightQuotient, $leftRemainder, $message), $message);
        $smallProduct = self::safeMultiply($rightRemainder, $leftRemainder, $message);
        $result = self::safeAdd($base, intdiv($smallProduct, $divisor), $message);
        if (($smallProduct % $divisor) * 2 >= $divisor) {
            $result = self::safeAdd($result, 1, $message);
        }

        return $result;
    }

    private function scenario(int $costMicros, int $yield, string $multiplier, array $ratio): array
    {
        $priceMinor = self::multiplyDivideRound($costMicros, $ratio[0], $ratio[1] * 10000, 'El precio sugerido excede el límite exacto permitido.');
        $metrics = $this->priceMetrics($costMicros, $yield, $priceMinor);

        return [
            'multiplier' => $multiplier,
            'suggested_price_minor' => (string) $priceMinor,
            ...$metrics,
        ];
    }

    private function priceMetrics(int $costMicros, int $yield, int $priceMinor): array
    {
        $saleMicros = self::minorToMicros($priceMinor, 'El precio excede el límite exacto permitido.');
        $profit = self::safeAdd($saleMicros, -$costMicros, 'La ganancia excede el límite exacto permitido.');
        $expected = self::safeMultiply($profit, $yield, 'La ganancia esperada excede el límite exacto permitido.');
        $marginTenths = $saleMicros === 0 ? null : self::signedDivideRound(self::safeMultiply($profit, 1000, 'El margen excede el límite exacto permitido.'), $saleMicros, 'El margen excede el límite exacto permitido.');

        return [
            'profit_per_unit_micros' => (string) $profit,
            'expected_yield_profit_micros' => (string) $expected,
            'margin_percent' => $marginTenths === null ? null : self::tenths($marginTenths),
        ];
    }

    private static function signedDivideRound(int $numerator, int $denominator, string $message): int
    {
        $negative = $numerator < 0;
        $value = self::multiplyDivideRound(abs($numerator), 1, $denominator, $message);

        return $negative ? -$value : $value;
    }

    private static function tenths(int $value): string
    {
        $negative = $value < 0;
        $absolute = abs($value);
        $formatted = intdiv($absolute, 10).'.'.($absolute % 10);

        return $negative ? '-'.$formatted : $formatted;
    }

    private function profilePayload($profile): ?array
    {
        if (! $profile) {
            return null;
        }

        return [
            'id' => $profile->id,
            'version_number' => (string) $profile->version_number,
            'reference_order_quantity' => (string) $profile->reference_order_quantity,
            'components' => $profile->components->map(fn ($component): array => [
                'concept' => $component->concept,
                'amount_minor' => (string) $component->amount_minor,
                'allocation' => $component->allocation,
            ])->values()->all(),
        ];
    }
}
