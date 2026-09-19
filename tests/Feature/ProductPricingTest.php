<?php

use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductCostProfile;
use App\Models\ProductPrice;
use App\Models\Recipe;
use App\Services\ChangeProductPrice;
use App\Services\ProductCosting;
use App\Services\RecordIngredientPurchase;
use App\Services\SaveProductProfile;
use App\Services\SaveRecipe;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function productPurchaseInput(array $overrides = []): array
{
    return array_replace([
        'ingredient_name' => 'Harina producto', 'presentation' => 'Bolsa de 1 kg',
        'purchase_quantity' => '1', 'purchase_unit' => 'kg', 'total_paid' => '42.00',
        'purchased_on' => '2026-09-18', 'store' => null, 'note' => null,
        'request_key' => (string) Str::uuid(),
    ], $overrides);
}

function productRecipeInput(int $ingredientId, array $overrides = []): array
{
    return array_replace([
        'name' => 'Producto de prueba', 'expected_yield' => '10', 'instructions' => null, 'notes' => null,
        'ingredients' => [['ingredient_id' => $ingredientId, 'quantity' => '1', 'unit' => 'kg']],
        'request_key' => (string) Str::uuid(),
    ], $overrides);
}

function productProfileInput(int $recipeId, array $overrides = []): array
{
    return array_replace([
        'recipe_id' => $recipeId, 'active' => true, 'reference_order_quantity' => '10',
        'components' => [
            ['concept' => 'Gas', 'amount' => '10.00', 'allocation' => 'batch'],
            ['concept' => 'Empaque', 'amount' => '1.25', 'allocation' => 'unit'],
            ['concept' => 'Entrega', 'amount' => '30.00', 'allocation' => 'order'],
        ],
        'request_key' => (string) Str::uuid(),
    ], $overrides);
}

function makeProductFixture(array $profileOverrides = []): array
{
    $purchase = app(RecordIngredientPurchase::class)->record(productPurchaseInput());
    $version = app(SaveRecipe::class)->save(productRecipeInput($purchase->ingredient_id));
    $product = app(SaveProductProfile::class)->create(productProfileInput($version->recipe_id, $profileOverrides));

    return [$product, $version, $purchase];
}

it('creates one product per recipe and keeps the sale unit as piece', function (): void {
    $purchase = app(RecordIngredientPurchase::class)->record(productPurchaseInput());
    $version = app(SaveRecipe::class)->save(productRecipeInput($purchase->ingredient_id));
    $input = productProfileInput($version->recipe_id, ['components' => []]);
    $product = app(SaveProductProfile::class)->create($input);
    $replay = app(SaveProductProfile::class)->create($input);

    expect($replay->id)->toBe($product->id)
        ->and($product->recipe_id)->toBe($version->recipe_id)->and($product->sale_unit)->toBe('piece')
        ->and(fn () => app(SaveProductProfile::class)->create(productProfileInput($version->recipe_id, ['components' => []])))
        ->toThrow(ValidationException::class);
    expect(Product::count())->toBe(1)->and(ProductCostProfile::count())->toBe(1);
});

it('allocates batch, unit and order costs exactly once', function (): void {
    [$product] = makeProductFixture();

    $cost = app(ProductCosting::class)->current($product);

    expect($cost['complete'])->toBeTrue()
        ->and($cost['unit_cost_micros'])->toBe('9450000')
        ->and($cost['breakdown']['batch'][0]['per_unit_micros'])->toBe('1000000')
        ->and($cost['breakdown']['unit'][0]['per_unit_micros'])->toBe('1250000')
        ->and($cost['breakdown']['order'][0]['per_unit_micros'])->toBe('3000000')
        ->and($cost['breakdown']['order'][0]['denominator'])->toBe('10');
});

it('changes only the order allocation when its explicit denominator changes', function (): void {
    [$product] = makeProductFixture();
    $first = app(ProductCosting::class)->current($product);
    $profile = $product->latestProfile()->firstOrFail();

    app(SaveProductProfile::class)->save(productProfileInput($product->recipe_id, [
        'base_profile_id' => $profile->id,
        'reference_order_quantity' => '20',
    ]), $product);
    $second = app(ProductCosting::class)->current($product->fresh());

    expect($first['unit_cost_micros'])->toBe('9450000')
        ->and($second['unit_cost_micros'])->toBe('7950000')
        ->and($second['breakdown']['order'][0]['denominator'])->toBe('20');
});

it('propagates incomplete recipe economics without substituting zero', function (): void {
    $ingredient = Ingredient::create(['name' => 'Canela sin compra', 'name_key' => hash('sha256', 'canela-sin-compra'), 'canonical_unit' => 'g']);
    $version = app(SaveRecipe::class)->save(productRecipeInput($ingredient->id, [
        'ingredients' => [['ingredient_id' => $ingredient->id, 'quantity' => '10', 'unit' => 'g']],
    ]));
    $product = app(SaveProductProfile::class)->create(productProfileInput($version->recipe_id, ['components' => []]));
    $cost = app(ProductCosting::class)->current($product);

    expect($cost['complete'])->toBeFalse()->and($cost['unit_cost_micros'])->toBeNull()->and($cost['scenarios'])->toBeEmpty();
});

it('keeps profile v1 immutable and rejects a stale profile edit', function (): void {
    [$product] = makeProductFixture();
    $first = $product->latestProfile()->with('components')->firstOrFail();
    $second = app(SaveProductProfile::class)->save(productProfileInput($product->recipe_id, [
        'base_profile_id' => $first->id, 'components' => [['concept' => 'Nuevo empaque', 'amount' => '2.00', 'allocation' => 'unit']],
    ]), $product);

    expect($second->latestProfile->version_number)->toBe('2')
        ->and($first->fresh()->components()->first()->concept)->toBe('Gas')
        ->and(fn () => app(SaveProductProfile::class)->save(productProfileInput($product->recipe_id, [
            'base_profile_id' => $first->id, 'components' => [],
        ]), $product))->toThrow(ConflictHttpException::class);
});

it('uses exact scenario prices and keeps multiplier distinct from margin', function (): void {
    [$product] = makeProductFixture();
    $scenarios = collect(app(ProductCosting::class)->current($product)['scenarios'])->keyBy('multiplier');

    expect($scenarios['2']['suggested_price_minor'])->toBe('1890')
        ->and($scenarios['2.5']['suggested_price_minor'])->toBe('2363')
        ->and($scenarios['3']['suggested_price_minor'])->toBe('2835')
        ->and($scenarios['3.5']['suggested_price_minor'])->toBe('3308')
        ->and($scenarios['2.5']['profit_per_unit_micros'])->toBe('14180000')
        ->and($scenarios['2.5']['expected_yield_profit_micros'])->toBe('141800000')
        ->and($scenarios['2.5']['margin_percent'])->toBe('60.0')
        ->and($scenarios['2.5']['multiplier'])->toBe('2.5');
});

it('rejects exact arithmetic overflow before returning an answer', function (): void {
    expect(fn () => ProductCosting::multiplyDivideRound(PHP_INT_MAX, 2, 1, 'overflow'))
        ->toThrow(ValidationException::class);
});

it('reports a loss for a manual price below current cost', function (): void {
    [$product] = makeProductFixture();
    app(ChangeProductPrice::class)->change($product, [
        'mode' => 'manual', 'price' => '1.00', 'confirmed' => true, 'request_key' => (string) Str::uuid(),
    ]);
    $cost = app(ProductCosting::class)->current($product->fresh());

    expect($cost['current_price_metrics']['profit_per_unit_micros'])->toBe('-8450000')
        ->and($cost['current_price_metrics']['margin_percent'])->toBe('-845.0');
});

it('appends prices, replays idempotent requests and orders the current price deterministically', function (): void {
    [$product] = makeProductFixture(['components' => []]);
    $key = (string) Str::uuid();
    $changer = app(ChangeProductPrice::class);
    $first = $changer->change($product, ['mode' => 'manual', 'price' => '20.00', 'confirmed' => true, 'request_key' => $key]);
    $replay = $changer->change($product, ['mode' => 'manual', 'price' => '20.00', 'confirmed' => true, 'request_key' => $key]);
    $second = $changer->change($product, ['mode' => 'manual', 'price' => '30.00', 'confirmed' => true, 'request_key' => (string) Str::uuid()]);

    expect($replay->id)->toBe($first->id)->and(ProductPrice::count())->toBe(2)
        ->and($product->fresh()->latestPrice->price_minor)->toBe('3000')
        ->and(fn () => $changer->change($product, ['mode' => 'manual', 'price' => '21.00', 'confirmed' => true, 'request_key' => $key]))
        ->toThrow(ValidationException::class)
        ->and(fn () => $second->update(['price_minor' => 1]))->toThrow(LogicException::class);
});

it('keeps current product cost dynamic without rewriting profile or price history', function (): void {
    [$product, $version] = makeProductFixture(['components' => []]);
    $profileSnapshot = $product->latestProfile()->with('components')->firstOrFail()->toArray();
    app(ChangeProductPrice::class)->change($product, ['mode' => 'manual', 'price' => '20.00', 'confirmed' => true, 'request_key' => (string) Str::uuid()]);
    $priceSnapshot = ProductPrice::sole()->toArray();
    app(RecordIngredientPurchase::class)->record(productPurchaseInput(['total_paid' => '50.00']));
    $current = app(ProductCosting::class)->current($product->fresh());

    expect($current['unit_cost_micros'])->toBe('5000000')
        ->and($product->latestProfile()->with('components')->firstOrFail()->toArray())->toEqualCanonicalizing($profileSnapshot)
        ->and(ProductPrice::sole()->toArray())->toEqualCanonicalizing($priceSnapshot)
        ->and($version->fresh()->snapshot_batch_cost_micros)->toBe('42000000');
});

it('keeps inactive products readable and renders product Inertia surfaces', function (): void {
    [$product] = makeProductFixture(['components' => [], 'active' => false]);

    expect($product->fresh()->active)->toBeFalse();
    $this->get(route('products.index'))->assertInertia(fn ($page) => $page->component('Products/Index')->has('products', 1));
    $this->get(route('products.show', $product))->assertInertia(fn ($page) => $page->component('Products/Show')->where('product.active', false)->where('product.cost.complete', true));
    $this->get(route('products.edit', $product))->assertInertia(fn ($page) => $page->component('Products/Edit')->where('product.active', false)->where('profile.components', []));
    $this->get(route('recipes.index'))->assertInertia(fn ($page) => $page->component('Recipes/Index'));
});
