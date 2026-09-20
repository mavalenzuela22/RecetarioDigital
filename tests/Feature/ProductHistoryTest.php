<?php

use App\Models\IngredientPurchase;
use App\Models\ProductPrice;
use App\Services\ChangeProductPrice;
use App\Services\ProductHistoryComparison;
use App\Services\RecordIngredientPurchase;
use App\Services\SaveProductProfile;
use App\Services\SaveRecipe;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function historyPurchase(string $name, string $price, string $date): IngredientPurchase
{
    return app(RecordIngredientPurchase::class)->record([
        'ingredient_name' => $name,
        'presentation' => 'Compra de '.$name,
        'purchase_quantity' => '1',
        'purchase_unit' => 'kg',
        'total_paid' => $price,
        'purchased_on' => $date,
        'store' => null,
        'note' => null,
        'request_key' => (string) Str::uuid(),
    ]);
}

function historyFixture(): array
{
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-17 10:00:00', 'America/Monterrey'));
    $purchase = historyPurchase('Harina histórica', '42.00', '2026-09-17');
    $version = app(SaveRecipe::class)->save([
        'name' => 'Galleta histórica',
        'expected_yield' => '10',
        'instructions' => null,
        'notes' => null,
        'ingredients' => [['ingredient_id' => $purchase->ingredient_id, 'quantity' => '1', 'unit' => 'kg']],
        'request_key' => (string) Str::uuid(),
    ]);
    $product = app(SaveProductProfile::class)->create([
        'recipe_id' => $version->recipe_id,
        'active' => true,
        'reference_order_quantity' => '10',
        'components' => [],
        'request_key' => (string) Str::uuid(),
    ]);
    app(ChangeProductPrice::class)->change($product, [
        'mode' => 'manual', 'price' => '20.00', 'confirmed' => true, 'request_key' => (string) Str::uuid(),
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-18 10:00:00', 'America/Monterrey'));
    historyPurchase('Harina histórica', '50.00', '2026-09-18');
    app(ChangeProductPrice::class)->change($product->fresh(), [
        'mode' => 'manual', 'price' => '25.00', 'confirmed' => true, 'request_key' => (string) Str::uuid(),
    ]);
    CarbonImmutable::setTestNow();

    return [$product->fresh(), $purchase];
}

it('selects local-day facts and reconstructs exact historical economics', function (): void {
    [$product] = historyFixture();
    $comparison = app(ProductHistoryComparison::class)->compare($product, '2026-09-17', '2026-09-18');

    expect($comparison['error'])->toBeNull()
        ->and($comparison['before']['complete'])->toBeTrue()
        ->and($comparison['now']['complete'])->toBeTrue()
        ->and($comparison['before']['economics']['unit_cost_micros'])->toBe('4200000')
        ->and($comparison['now']['economics']['unit_cost_micros'])->toBe('5000000')
        ->and($comparison['before']['economics']['profit_per_unit_micros'])->toBe('15800000')
        ->and($comparison['now']['economics']['profit_per_unit_micros'])->toBe('20000000')
        ->and($comparison['before']['economics']['margin_percent'])->toBe('79.0')
        ->and($comparison['now']['economics']['margin_percent'])->toBe('80.0')
        ->and($comparison['cost_delta_micros'])->toBe('800000')
        ->and($comparison['cost_delta_abs_micros'])->toBe('800000')
        ->and($comparison['cost_delta_percent'])->toBe('19.0')
        ->and($comparison['before']['recipe']['lines'][0]['purchase_date'])->toBe('2026-09-17')
        ->and($comparison['now']['recipe']['lines'][0]['purchase_date'])->toBe('2026-09-18');
});

it('uses purchased_on and the highest id for same-day ingredient purchases', function (): void {
    [$product, $first] = historyFixture();
    $second = historyPurchase('Harina histórica', '60.00', '2026-09-17');
    expect($second->id)->toBeGreaterThan($first->id);

    $comparison = app(ProductHistoryComparison::class)->compare($product, '2026-09-17', '2026-09-17');

    expect($comparison['before']['recipe']['lines'][0]['purchase_id'])->toBe($second->id)
        ->and($comparison['before']['economics']['unit_cost_micros'])->toBe('6000000');
});

it('keeps price visible but marks economics pending when a recipe line lacks an effective purchase', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-17 10:00:00', 'America/Monterrey'));
    $purchase = historyPurchase('Harina completa', '42.00', '2026-09-17');
    $missing = \App\Models\Ingredient::create(['name' => 'Canela histórica', 'name_key' => hash('sha256', 'canela-historica'), 'canonical_unit' => 'g']);
    $version = app(SaveRecipe::class)->save([
        'name' => 'Producto incompleto', 'expected_yield' => '10', 'instructions' => null, 'notes' => null,
        'ingredients' => [
            ['ingredient_id' => $purchase->ingredient_id, 'quantity' => '1', 'unit' => 'kg'],
            ['ingredient_id' => $missing->id, 'quantity' => '10', 'unit' => 'g'],
        ],
        'request_key' => (string) Str::uuid(),
    ]);
    $product = app(SaveProductProfile::class)->create(['recipe_id' => $version->recipe_id, 'active' => true, 'reference_order_quantity' => '10', 'components' => [], 'request_key' => (string) Str::uuid()]);
    app(ChangeProductPrice::class)->change($product, ['mode' => 'manual', 'price' => '20.00', 'confirmed' => true, 'request_key' => (string) Str::uuid()]);
    CarbonImmutable::setTestNow();

    $side = app(ProductHistoryComparison::class)->compare($product->fresh(), '2026-09-17', '2026-09-17')['before'];

    expect($side['available'])->toBeTrue()
        ->and($side['complete'])->toBeFalse()
        ->and($side['economics']['sale_price_minor'])->toBe('2000')
        ->and($side['economics']['unit_cost_micros'])->toBeNull()
        ->and($side['economics']['profit_per_unit_micros'])->toBeNull()
        ->and($side['provenance']['ingredients'][1]['purchase_date'])->toBeNull();
});

it('suppresses drivers when recipe economics change and exposes profile changes separately', function (): void {
    [$product] = historyFixture();
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-09-18 15:00:00', 'America/Monterrey'));
    $recipe = $product->recipe;
    $latest = $recipe->latestVersion;
    app(SaveRecipe::class)->save([
        'name' => 'Galleta histórica', 'expected_yield' => '12', 'instructions' => null, 'notes' => null,
        'ingredients' => [['ingredient_id' => $latest->lines()->firstOrFail()->ingredient_id, 'quantity' => '1', 'unit' => 'kg']],
        'base_version_id' => $latest->id,
        'request_key' => (string) Str::uuid(),
    ], $recipe);
    $profile = $product->latestProfile;
    app(SaveProductProfile::class)->save([
        'recipe_id' => $product->recipe_id, 'active' => true, 'reference_order_quantity' => '8', 'components' => [],
        'base_profile_id' => $profile->id, 'request_key' => (string) Str::uuid(),
    ], $product);
    CarbonImmutable::setTestNow();

    $comparison = app(ProductHistoryComparison::class)->compare($product->fresh(), '2026-09-17', '2026-09-18');

    expect($comparison['recipe_changed'])->toBeTrue()
        ->and($comparison['profile_changed'])->toBeTrue()
        ->and($comparison['drivers'])->toBeNull();
});

it('rejects reversed ranges, exposes the accepted history screen, and links pricing', function (): void {
    [$product] = historyFixture();
    $comparison = app(ProductHistoryComparison::class)->compare($product, '2026-09-18', '2026-09-17');

    expect($comparison['error'])->toBe('La fecha final debe ser igual o posterior a la inicial.');
    $this->get(route('products.history', [$product, 'asOf' => '2026-09-18', 'until' => '2026-09-17']))
        ->assertInertia(fn ($page) => $page->component('Products/History')->where('comparison.error', 'La fecha final debe ser igual o posterior a la inicial.')->where('productUrl', route('products.show', $product))->where('pricingUrl', route('products.show', $product)));
    $this->get(route('products.show', $product))
        ->assertInertia(fn ($page) => $page->component('Products/Show')->where('historyUrl', route('products.history', $product)));
});

it('includes the local business-day close and excludes the next local boundary', function (): void {
    [$product] = historyFixture();
    $timezone = 'America/Monterrey';
    $recipe = $product->recipe;
    $version = $recipe->latestVersion;
    $profile = $product->latestProfile;
    $price = ProductPrice::query()->latest('id')->firstOrFail();
    $localClose = CarbonImmutable::parse('2026-09-18 23:59:59', $timezone);
    $justAfterBoundary = CarbonImmutable::parse('2026-09-19 00:00:00', $timezone);

    DB::table('recipe_versions')->where('id', $version->id)->update(['created_at' => $localClose->format('Y-m-d H:i:s')]);
    DB::table('product_cost_profiles')->where('id', $profile->id)->update(['created_at' => $localClose->format('Y-m-d H:i:s')]);
    DB::table('product_prices')->where('id', $price->id)->update(['effective_at' => $localClose->format('Y-m-d H:i:s')]);

    $atClose = app(ProductHistoryComparison::class)->compare($product, '2026-09-18', '2026-09-18')['before'];

    DB::table('recipe_versions')->where('id', $version->id)->update(['created_at' => $justAfterBoundary->format('Y-m-d H:i:s')]);
    DB::table('product_cost_profiles')->where('id', $profile->id)->update(['created_at' => $justAfterBoundary->format('Y-m-d H:i:s')]);
    DB::table('product_prices')->where('id', $price->id)->update(['effective_at' => $justAfterBoundary->format('Y-m-d H:i:s')]);

    $beforeBoundary = app(ProductHistoryComparison::class)->compare($product, '2026-09-18', '2026-09-18')['before'];
    $afterBoundary = app(ProductHistoryComparison::class)->compare($product, '2026-09-19', '2026-09-19')['before'];

    expect($atClose['available'])->toBeTrue()
        ->and($beforeBoundary['available'])->toBeFalse()
        ->and($beforeBoundary['message'])->toBe(ProductHistoryComparison::MISSING_REFERENCE)
        ->and($afterBoundary['available'])->toBeTrue();
});
