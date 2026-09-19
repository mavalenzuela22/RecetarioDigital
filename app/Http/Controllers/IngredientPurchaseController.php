<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreIngredientPurchaseRequest;
use App\Models\Ingredient;
use App\Services\RecordIngredientPurchase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class IngredientPurchaseController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Ingredients/Index', [
            'ingredients' => Ingredient::with('currentPurchase')->orderBy('name')->get()->map(fn ($ingredient) => [
                ...$ingredient->toArray(), 'url' => route('ingredients.show', $ingredient),
            ]),
            'createUrl' => route('purchases.create'),
        ]);
    }

    public function show(Request $request, Ingredient $ingredient): Response
    {
        return Inertia::render('Ingredients/Show', [
            'ingredient' => $ingredient->load('currentPurchase'),
            'purchases' => $ingredient->purchases()->paginate(30)->withQueryString(),
            'success' => $request->session()->get('success'),
            'createUrl' => route('purchases.create', ['ingredient' => $ingredient->id]),
            'indexUrl' => route('ingredients.index'),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Purchases/Create', [
            'ingredients' => Ingredient::orderBy('name')->get(['id', 'name', 'canonical_unit']),
            'selectedIngredient' => Ingredient::find($request->integer('ingredient'))?->name ?? '',
            'requestKey' => (string) Str::uuid(),
            'storeUrl' => route('purchases.store'), 'indexUrl' => route('ingredients.index'),
        ]);
    }

    public function store(StoreIngredientPurchaseRequest $request, RecordIngredientPurchase $recorder): RedirectResponse
    {
        $purchase = $recorder->record($request->validated());

        return to_route('ingredients.show', $purchase->ingredient_id, 303)->with('success', 'Compra registrada.');
    }
}
