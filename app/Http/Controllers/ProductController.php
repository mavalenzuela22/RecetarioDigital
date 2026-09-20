<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangeProductPriceRequest;
use App\Http\Requests\StoreProductRequest;
use App\Models\Product;
use App\Models\Recipe;
use App\Services\ChangeProductPrice;
use App\Services\ProductCosting;
use App\Services\ProductHistoryComparison;
use App\Services\SaveProductProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(ProductCosting $costing): Response
    {
        $products = Product::with(['recipe.latestVersion', 'latestProfile.components', 'latestPrice'])->orderBy('id')->get()->map(fn (Product $product): array => $this->summary($product, $costing));
        $configured = $products->pluck('recipe_id')->all();
        $recipes = Recipe::with('latestVersion')->orderBy('name')->get()->reject(fn (Recipe $recipe): bool => in_array($recipe->id, $configured, true))->map(fn (Recipe $recipe): array => [
            'id' => $recipe->id,
            'name' => $recipe->latestVersion?->name ?? $recipe->name,
            'url' => route('products.create', ['recipe' => $recipe->id]),
        ])->values();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'recipes' => $recipes,
            'recipesUrl' => route('recipes.index'),
        ]);
    }

    public function create(Request $request, ProductCosting $costing): Response
    {
        $recipe = Recipe::with('latestVersion')->findOrFail($request->integer('recipe'));
        abort_if(Product::where('recipe_id', $recipe->id)->exists(), 409, 'Esta receta ya tiene un producto.');

        return Inertia::render('Products/Edit', $this->formProps(null, $recipe, null, $costing));
    }

    public function edit(Product $product, ProductCosting $costing): Response
    {
        return Inertia::render('Products/Edit', $this->formProps($product, $product->recipe()->with('latestVersion')->firstOrFail(), $costing->current($product), $costing));
    }

    public function show(Request $request, Product $product, ProductCosting $costing): Response
    {
        return Inertia::render('Products/Show', [
            'product' => $this->payload($product, $costing->current($product)),
            'success' => $request->session()->get('success'),
            'indexUrl' => route('products.index'),
            'editUrl' => route('products.edit', $product),
            'scenarioPriceUrl' => route('products.price', $product),
            'historyUrl' => route('products.history', $product),
            'scenarioRequestKey' => (string) Str::uuid(),
            'manualRequestKey' => (string) Str::uuid(),
        ]);
    }

    public function history(Request $request, Product $product, ProductHistoryComparison $history): Response
    {
        $businessDate = now(config('app.timezone'))->format('Y-m-d');
        $asOf = (string) $request->query('asOf', $businessDate);
        $until = (string) $request->query('until', $businessDate);
        $comparison = null;
        if ($request->has('asOf') || $request->has('until')) {
            if ($asOf !== '' && $until !== '') {
                $comparison = $history->compare($product, $asOf, $until);
            }
        }

        $product->loadMissing('recipe.latestVersion');

        return Inertia::render('Products/History', [
            'product' => [
                'id' => $product->id,
                'name' => $product->recipe?->latestVersion?->name ?? $product->recipe?->name,
            ],
            'asOf' => $asOf,
            'until' => $until,
            'comparison' => $comparison,
            'productUrl' => route('products.show', $product),
            'pricingUrl' => route('products.show', $product),
        ]);
    }

    public function store(StoreProductRequest $request, SaveProductProfile $saver): RedirectResponse
    {
        $product = $saver->create($request->validated());

        return to_route('products.show', $product, 303)->with('success', 'Producto configurado.');
    }

    public function update(StoreProductRequest $request, Product $product, SaveProductProfile $saver): RedirectResponse
    {
        $product = $saver->save($request->validated(), $product);

        return to_route('products.show', $product, 303)->with('success', 'Configuración guardada como nueva versión.');
    }

    public function price(ChangeProductPriceRequest $request, Product $product, ChangeProductPrice $changer): RedirectResponse
    {
        $changer->change($product, $request->validated());

        return to_route('products.show', $product, 303)->with('success', 'Precio guardado.');
    }

    private function summary(Product $product, ProductCosting $costing): array
    {
        $cost = $costing->current($product);

        return [
            'id' => $product->id,
            'recipe_id' => $product->recipe_id,
            'name' => $product->recipe?->latestVersion?->name ?? $product->recipe?->name,
            'active' => $product->active,
            'complete' => $cost['complete'],
            'unit_cost_micros' => $cost['unit_cost_micros'],
            'current_price_minor' => $cost['current_price_minor'],
            'url' => route('products.show', $product),
        ];
    }

    private function formProps(?Product $product, Recipe $recipe, ?array $cost, ProductCosting $costing): array
    {
        $current = $product ? ($cost ?? $costing->current($product)) : null;
        $profile = $current['profile'] ?? null;

        return [
            'product' => $product ? [
                'id' => $product->id,
                'active' => $product->active,
                'base_profile_id' => $profile['id'] ?? null,
            ] : null,
            'recipe' => ['id' => $recipe->id, 'name' => $recipe->latestVersion?->name ?? $recipe->name, 'expected_yield' => $recipe->latestVersion?->expected_yield],
            'profile' => $profile ? [
                'reference_order_quantity' => $profile['reference_order_quantity'],
                'components' => array_map(fn (array $component): array => [
                    'concept' => $component['concept'],
                    'amount' => $this->minorDecimal($component['amount_minor']),
                    'allocation' => $component['allocation'],
                ], $profile['components']),
            ] : ['reference_order_quantity' => (string) ($recipe->latestVersion?->expected_yield ?? '1'), 'components' => []],
            'cost' => $current,
            'requestKey' => (string) Str::uuid(),
            'storeUrl' => $product ? route('products.update', $product) : route('products.store'),
            'backUrl' => $product ? route('products.show', $product) : route('products.index'),
        ];
    }

    private function payload(Product $product, array $cost): array
    {
        $product->loadMissing('recipe.latestVersion');

        return [
            'id' => $product->id,
            'name' => $product->recipe?->latestVersion?->name ?? $product->recipe?->name,
            'active' => $product->active,
            'sale_unit' => $product->sale_unit,
            'recipe_id' => $product->recipe_id,
            'cost' => $cost,
        ];
    }

    private function minorDecimal(string $value): string
    {
        $digits = str_pad($value, 3, '0', STR_PAD_LEFT);
        $whole = substr($digits, 0, -2);
        $fraction = rtrim(substr($digits, -2), '0');

        return $whole.($fraction !== '' ? '.'.$fraction : '');
    }
}
