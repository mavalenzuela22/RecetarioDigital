<?php

use App\Models\Ingredient;
use App\Models\IngredientPurchase;
use App\Models\Recipe;
use App\Models\RecipeVersion;
use App\Services\RecordIngredientPurchase;
use App\Services\SaveRecipe;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->withoutMiddleware(ValidateCsrfToken::class);
});

function recipePurchaseInput(array $overrides = []): array
{
    return array_replace([
        'ingredient_name' => 'Harina de trigo', 'presentation' => 'Bolsa de 1 kg',
        'purchase_quantity' => '1', 'purchase_unit' => 'kg', 'total_paid' => '42.00',
        'purchased_on' => '2026-09-18', 'store' => null, 'note' => null,
        'request_key' => (string) Str::uuid(),
    ], $overrides);
}

function recipeInput(array $lines, array $overrides = []): array
{
    return array_replace([
        'name' => 'Roles de canela', 'expected_yield' => '12', 'instructions' => 'Mezclar y hornear.', 'notes' => 'Enfriar antes de empacar.',
        'ingredients' => $lines, 'request_key' => (string) Str::uuid(),
    ], $overrides);
}

function makeRecipeIngredients(): array
{
    $recorder = app(RecordIngredientPurchase::class);
    $flour = $recorder->record(recipePurchaseInput());
    $milk = $recorder->record(recipePurchaseInput([
        'ingredient_name' => 'Leche', 'presentation' => 'Litro', 'purchase_unit' => 'l', 'total_paid' => '20.00',
    ]));

    return [$flour->ingredient_id, $milk->ingredient_id];
}

it('normalizes compatible recipe units and uses exact half-up usage cost', function (): void {
    [$flour] = makeRecipeIngredients();
    $version = app(SaveRecipe::class)->save(recipeInput([
        ['ingredient_id' => $flour, 'quantity' => '1', 'unit' => 'kg'],
    ], ['expected_yield' => '2']));

    expect($version->lines->first()->normalized_quantity_milli)->toBe('1000000')
        ->and($version->snapshot_batch_cost_micros)->toBe('42000000')
        ->and($version->snapshot_unit_cost_micros)->toBe('21000000');
});

it('rounds an exact half micro upward without binary floating point', function (): void {
    $purchase = app(RecordIngredientPurchase::class)->record(recipePurchaseInput([
        'purchase_quantity' => '10000', 'purchase_unit' => 'g', 'total_paid' => '0.01',
    ]));
    $version = app(SaveRecipe::class)->save(recipeInput([
        ['ingredient_id' => $purchase->ingredient_id, 'quantity' => '0.5', 'unit' => 'g'],
    ]));

    expect($version->snapshot_batch_cost_micros)->toBe('1');
});

it('rejects incompatible units and duplicate ingredient lines', function (): void {
    [$flour] = makeRecipeIngredients();
    $service = app(SaveRecipe::class);
    expect(fn () => $service->save(recipeInput([
        ['ingredient_id' => $flour, 'quantity' => '1', 'unit' => 'l'],
    ])))->toThrow(ValidationException::class);
    expect(fn () => $service->save(recipeInput([
        ['ingredient_id' => $flour, 'quantity' => '1', 'unit' => 'kg'],
        ['ingredient_id' => $flour, 'quantity' => '2', 'unit' => 'kg'],
    ])))->toThrow(ValidationException::class);
    expect(RecipeVersion::count())->toBe(0);
});

it('rejects arithmetic overflow before creating a version', function (): void {
    $purchase = app(RecordIngredientPurchase::class)->record(recipePurchaseInput([
        'purchase_quantity' => '0.001', 'purchase_unit' => 'g', 'total_paid' => '999999999.99',
    ]));
    expect($purchase->normalized_unit_cost_micros)->toBe('999999999990000000');
    expect(fn () => app(SaveRecipe::class)->save(recipeInput([
        ['ingredient_id' => $purchase->ingredient_id, 'quantity' => '10', 'unit' => 'g'],
    ])))->toThrow(ValidationException::class);
    expect(Recipe::count())->toBe(0);
});

it('allows incomplete cost without substituting zero', function (): void {
    [$flour] = makeRecipeIngredients();
    $unpriced = Ingredient::create(['name' => 'Canela', 'name_key' => hash('sha256', 'canela'), 'canonical_unit' => 'g']);
    $version = app(SaveRecipe::class)->save(recipeInput([
        ['ingredient_id' => $flour, 'quantity' => '1', 'unit' => 'kg'],
        ['ingredient_id' => $unpriced->id, 'quantity' => '10', 'unit' => 'g'],
    ]));

    expect($version->snapshot_batch_cost_micros)->toBeNull()
        ->and($version->lines()->where('ingredient_id', $unpriced->id)->value('snapshot_usage_cost_micros'))->toBeNull()
        ->and(app(SaveRecipe::class)->currentCost($version)['complete'])->toBeFalse();
});

it('keeps version snapshots immutable while current cost follows latest and backdated purchases', function (): void {
    [$flour, $milk] = makeRecipeIngredients();
    $service = app(SaveRecipe::class);
    $first = $service->save(recipeInput([
        ['ingredient_id' => $flour, 'quantity' => '1', 'unit' => 'kg'],
        ['ingredient_id' => $milk, 'quantity' => '1', 'unit' => 'l'],
    ], ['expected_yield' => '10']));
    $snapshot = $first->fresh()->getAttributes();

    app(RecordIngredientPurchase::class)->record(recipePurchaseInput([
        'total_paid' => '50.00', 'request_key' => (string) Str::uuid(),
    ]));
    app(RecordIngredientPurchase::class)->record(recipePurchaseInput([
        'total_paid' => '10.00', 'purchased_on' => '2020-01-01', 'request_key' => (string) Str::uuid(),
    ]));
    $current = $service->currentCost($first->fresh());

    expect($current['complete'])->toBeTrue()
        ->and($current['batch_cost_micros'])->toBe('70000000')
        ->and($first->fresh()->getAttributes())->toEqualCanonicalizing($snapshot)
        ->and(IngredientPurchase::where('ingredient_id', $flour)->count())->toBe(3);
});

it('creates version two, rejects stale bases, and replays an identical request', function (): void {
    [$flour] = makeRecipeIngredients();
    $service = app(SaveRecipe::class);
    $input = recipeInput([['ingredient_id' => $flour, 'quantity' => '1', 'unit' => 'kg']]);
    $first = $service->save($input);
    $replay = $service->save($input);
    expect($replay->id)->toBe($first->id)->and(RecipeVersion::count())->toBe(1);

    $second = $service->save(recipeInput([['ingredient_id' => $flour, 'quantity' => '500', 'unit' => 'g']], [
        'base_version_id' => $first->id,
    ]), $first->recipe);
    expect($second->version_number)->toBe(2)->and($first->fresh()->name)->toBe('Roles de canela');

    expect(fn () => $service->save(recipeInput([['ingredient_id' => $flour, 'quantity' => '250', 'unit' => 'g']], [
        'base_version_id' => $first->id,
    ]), $first->recipe))->toThrow(ConflictHttpException::class);
    expect(fn () => $service->save(recipeInput([['ingredient_id' => $flour, 'quantity' => '2', 'unit' => 'kg']], [
        'request_key' => $input['request_key'],
    ])))->toThrow(ValidationException::class);
});

it('validates and stores an optional image through Laravel storage', function (): void {
    Storage::fake('public');
    [$flour] = makeRecipeIngredients();
    $input = recipeInput([['ingredient_id' => $flour, 'quantity' => '1', 'unit' => 'kg']], ['image' => UploadedFile::fake()->create('roles.webp', 1, 'image/webp')]);
    $this->post(route('recipes.store'), $input)->assertStatus(303)->assertSessionHas('success', 'Receta guardada como nueva versión.');
    $version = RecipeVersion::sole();
    expect($version->image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($version->image_path);
    $second = app(SaveRecipe::class)->save(recipeInput([
        ['ingredient_id' => $flour, 'quantity' => '500', 'unit' => 'g'],
    ], ['base_version_id' => $version->id] ), Recipe::sole());
    expect($second->image_path)->toBe($version->image_path)
        ->and($version->fresh()->image_path)->toBe($version->image_path);
});

it('renders the recipe list, detail and edit flow through Inertia', function (): void {
    [$flour] = makeRecipeIngredients();
    $this->get(route('recipes.index'))->assertInertia(fn ($page) => $page->component('Recipes/Index')->has('recipes', 0));
    $this->post(route('recipes.store'), recipeInput([['ingredient_id' => $flour, 'quantity' => '1', 'unit' => 'kg']]))->assertStatus(303);
    $recipe = Recipe::sole();
    $this->get(route('recipes.show', $recipe))->assertInertia(fn ($page) => $page->component('Recipes/Show')->where('recipe.version_number', 1)->where('recipe.cost.complete', true)->where('success', 'Receta guardada como nueva versión.'));
    $this->get(route('recipes.edit', $recipe))->assertInertia(fn ($page) => $page->component('Recipes/Create')->where('recipe.version_number', 1)->has('ingredients', 2));
});
