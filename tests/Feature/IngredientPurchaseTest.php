<?php

use App\Models\Ingredient;
use App\Models\IngredientPurchase;
use App\Services\RecordIngredientPurchase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function purchaseInput(array $overrides = []): array
{
    return array_replace([
        'ingredient_name' => 'Harina de trigo', 'presentation' => 'Bolsa de 1 kg',
        'purchase_quantity' => '1', 'purchase_unit' => 'kg', 'total_paid' => '42.00',
        'purchased_on' => '2026-09-18', 'store' => 'Mercado', 'note' => 'Para pan',
        'request_key' => (string) Str::uuid(),
    ], $overrides);
}

it('persists exact quantities and rounded micros without floats', function ($quantity, $unit, $paid, $normalized, $micros, $canonical): void {
    $this->post(route('purchases.store'), purchaseInput([
        'purchase_quantity' => $quantity, 'purchase_unit' => $unit, 'total_paid' => $paid,
    ]))->assertSessionHasNoErrors()->assertRedirect(route('ingredients.show', 1));
    $purchase = IngredientPurchase::sole();
    expect($purchase->normalized_quantity_milli)->toBe($normalized)
        ->and($purchase->normalized_unit_cost_micros)->toBe($micros)
        ->and($purchase->ingredient->canonical_unit)->toBe($canonical);
})->with([
    '1 kg -> 1000 g, 42/1000 = 0.042000' => ['1', 'kg', '42.00', '1000000', '42000', 'g'],
    '1 l -> 1000 ml' => ['1', 'l', '20.00', '1000000', '20000', 'ml'],
    'unchanged grams' => ['250', 'g', '12.50', '250000', '50000', 'g'],
    'unchanged milliliters' => ['500', 'ml', '25', '500000', '50000', 'ml'],
    'pieces repeating down' => ['3', 'piece', '1', '3000', '333333', 'piece'],
    'pieces repeating up' => ['3', 'piece', '2', '3000', '666667', 'piece'],
    'exact half rounds up' => ['20000', 'g', '0.01', '20000000', '1', 'g'],
    'below half rounds down' => ['20001', 'g', '0.01', '20001000', '0', 'g'],
    'decimal commas' => ['1,250', 'kg', '42,50', '1250000', '34000', 'g'],
    'maximum numerator remains an integer' => ['0.001', 'g', '999999999.99', '1', '999999999990000000', 'g'],
    'maximum normalized quantity remains an integer' => ['999999999.999', 'kg', '0.01', '999999999999000', '0', 'g'],
]);

it('keeps append-only history in effective date then persisted ID order', function (): void {
    $recorder = app(RecordIngredientPurchase::class);
    $old = $recorder->record(purchaseInput(['purchased_on' => '2026-09-01']));
    $original = $old->getAttributes();
    $new = $recorder->record(purchaseInput(['total_paid' => '50.00']));
    expect($new->ingredient->currentPurchase->id)->toBe($new->id);
    $backdated = $recorder->record(purchaseInput(['total_paid' => '10.00', 'purchased_on' => '2025-01-01']));
    expect($new->ingredient->fresh()->currentPurchase->id)->toBe($new->id);
    $tie = $recorder->record(purchaseInput(['total_paid' => '60.00']));
    expect($tie->ingredient->fresh()->currentPurchase->id)->toBe($tie->id)
        ->and($old->fresh()->getAttributes())->toEqualCanonicalizing($original);
    $this->get(route('ingredients.show', $old->ingredient_id))->assertInertia(fn (Assert $page) => $page
        ->component('Ingredients/Show')->where('ingredient.current_purchase.id', $tie->id)
        ->where('ingredient.current_purchase.normalized_unit_cost_micros', '60000')
        ->has('purchases.data', 4)
        ->where('purchases.data.0.id', $tie->id)->where('purchases.data.1.id', $new->id)
        ->where('purchases.data.2.id', $old->id)->where('purchases.data.3.id', $backdated->id));
    foreach (['put', 'patch', 'delete'] as $method) {
        $this->{$method}('/compras/'.$old->id, [])->assertNotFound();
    }
    expect(fn () => $old->update(['total_paid_minor' => 1]))->toThrow(LogicException::class);
    expect(fn () => $old->fresh()->delete())->toThrow(LogicException::class);
});

it('replays a UUID without creating another purchase or ingredient', function (): void {
    $input = purchaseInput();
    $this->post(route('purchases.store'), $input)->assertStatus(303)->assertSessionHas('success', 'Compra registrada.');
    $this->post(route('purchases.store'), $input)->assertStatus(303)->assertSessionHasNoErrors();
    expect(Ingredient::count())->toBe(1)->and(IngredientPurchase::count())->toBe(1);
    $this->postJson(route('purchases.store'), array_replace($input, ['total_paid' => '43']))
        ->assertUnprocessable()->assertJsonValidationErrors('request_key');
    expect(IngredientPurchase::sole()->total_paid_minor)->toBe('4200');
});

it('enforces unique request keys at database level', function (): void {
    $purchase = app(RecordIngredientPurchase::class)->record(purchaseInput());
    $duplicate = $purchase->getAttributes();
    unset($duplicate['id']);
    expect(fn () => Illuminate\Support\Facades\DB::table('ingredient_purchases')->insert($duplicate))
        ->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('recognizes existing names and rejects incompatible dimensions atomically', function (): void {
    $purchase = app(RecordIngredientPurchase::class)->record(purchaseInput());
    $this->postJson(route('purchases.store'), purchaseInput(['ingredient_name' => ' HARINA   DE trigo ', 'purchase_unit' => 'l']))
        ->assertUnprocessable()->assertJsonValidationErrors('purchase_unit');
    expect(Ingredient::count())->toBe(1)->and(IngredientPurchase::count())->toBe(1)
        ->and($purchase->ingredient->fresh()->canonical_unit)->toBe('g');
    $this->post(route('purchases.store'), purchaseInput(['ingredient_name' => 'harina de trigo', 'purchase_unit' => 'g']))
        ->assertSessionHasNoErrors();
    expect(Ingredient::count())->toBe(1)->and(IngredientPurchase::count())->toBe(2);
    expect(fn () => $purchase->ingredient->update(['canonical_unit' => 'ml']))->toThrow(LogicException::class);
});

it('rejects invalid numeric strings before writing any records', function ($field, $value): void {
    $this->postJson(route('purchases.store'), purchaseInput([$field => $value]))
        ->assertUnprocessable()->assertJsonValidationErrors($field);
    expect(Ingredient::count())->toBe(0)->and(IngredientPurchase::count())->toBe(0);
})->with([
    ['total_paid', '1,234'], ['total_paid', '1e2'], ['total_paid', '1.234'],
    ['total_paid', '1000000000'], ['total_paid', 42.5], ['total_paid', '-1'],
    ['total_paid', '0'], ['total_paid', '1,000.00'], ['purchase_quantity', '0'],
    ['purchase_quantity', '1.0001'], ['purchase_quantity', '1000000000'],
    ['purchase_quantity', ['1']], ['total_paid', 'NaN'], ['purchase_quantity', ''],
]);

it('exposes Inertia validation errors and preserves submitted input', function (): void {
    $input = purchaseInput(['total_paid' => '0']);
    $this->from(route('purchases.create'))->post(route('purchases.store'), $input)
        ->assertRedirect(route('purchases.create'))->assertSessionHasErrors('total_paid')
        ->assertSessionHasInput('ingredient_name', $input['ingredient_name'])
        ->assertSessionHasInput('presentation', $input['presentation'])
        ->assertSessionHasInput('purchase_quantity', '1')
        ->assertSessionHasInput('store', 'Mercado')->assertSessionHasInput('note', 'Para pan')
        ->assertSessionHasInput('request_key', $input['request_key']);
    $this->get(route('purchases.create'))->assertInertia(fn (Assert $page) => $page
        ->component('Purchases/Create')->has('errors.total_paid')->has('requestKey')->has('storeUrl'));
    expect(IngredientPurchase::count())->toBe(0);
});

it('renders the overview, selected form, durable history and confirmed success', function (): void {
    $this->get(route('ingredients.index'))->assertInertia(fn (Assert $page) => $page->component('Ingredients/Index')->has('ingredients', 0));
    $this->post(route('purchases.store'), purchaseInput())->assertStatus(303);
    $this->get(route('ingredients.show', 1))->assertInertia(fn (Assert $page) => $page
        ->component('Ingredients/Show')->where('success', 'Compra registrada.')
        ->where('purchases.data.0.total_paid_minor', '4200')->where('purchases.data.0.purchase_quantity_milli', '1000')
        ->where('purchases.data.0.presentation', 'Bolsa de 1 kg')->where('purchases.data.0.store', 'Mercado')->where('purchases.data.0.note', 'Para pan'));
    $this->get(route('ingredients.index'))->assertInertia(fn (Assert $page) => $page
        ->has('ingredients', 1)->where('ingredients.0.current_purchase.normalized_unit_cost_micros', '42000'));
    $this->get(route('purchases.create', ['ingredient' => 1]))->assertInertia(fn (Assert $page) => $page
        ->where('selectedIngredient', 'Harina de trigo')->has('ingredients', 1));
    $this->get('/ingredientes/999')->assertNotFound();
});
