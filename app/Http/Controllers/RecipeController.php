<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecipeRequest;
use App\Models\Ingredient;
use App\Models\Recipe;
use App\Models\RecipeVersion;
use App\Services\SaveRecipe;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class RecipeController extends Controller
{
    public function index(SaveRecipe $costing): Response
    {
        $recipes = Recipe::with('latestVersion')->orderBy('name')->get()->map(function (Recipe $recipe) use ($costing): array {
            $version = $recipe->latestVersion;
            $cost = $version ? $costing->currentCost($version) : null;

            return [
                'id' => $recipe->id,
                'name' => $version?->name ?? $recipe->name,
                'version_number' => $version?->version_number,
                'unit_cost_micros' => $cost['unit_cost_micros'] ?? null,
                'complete' => $cost['complete'] ?? false,
                'url' => route('recipes.show', $recipe),
            ];
        });

        return Inertia::render('Recipes/Index', [
            'recipes' => $recipes,
            'createUrl' => route('recipes.create'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Recipes/Create', $this->formProps(null));
    }

    public function edit(Recipe $recipe, SaveRecipe $costing): Response
    {
        $recipe->load('latestVersion');
        return Inertia::render('Recipes/Create', $this->formProps($recipe, $recipe->latestVersion ? $costing->currentCost($recipe->latestVersion) : null));
    }

    public function show(Request $request, Recipe $recipe, SaveRecipe $costing): Response
    {
        $recipe->load('latestVersion');
        $version = $recipe->latestVersion;
        abort_unless($version, 404);
        $version->load(['lines.ingredient', 'lines.snapshotPurchase']);

        return Inertia::render('Recipes/Show', [
            'recipe' => $this->recipePayload($recipe, $version, $costing->currentCost($version)),
            'success' => $request->session()->get('success'),
            'editUrl' => route('recipes.edit', $recipe),
            'indexUrl' => route('recipes.index'),
        ]);
    }

    public function image(Recipe $recipe, RecipeVersion $version)
    {
        abort_unless((int) $version->recipe_id === (int) $recipe->id, 404);
        abort_unless($version->image_path && Storage::disk('local')->exists($version->image_path), 404);

        return Storage::disk('local')->response($version->image_path);
    }

    public function store(StoreRecipeRequest $request, SaveRecipe $saver): RedirectResponse
    {
        $version = $saver->save($request->validated());

        return to_route('recipes.show', $version->recipe_id, 303)->with('success', 'Receta guardada como nueva versión.');
    }

    public function update(StoreRecipeRequest $request, Recipe $recipe, SaveRecipe $saver): RedirectResponse
    {
        try {
            $version = $saver->save($request->validated(), $recipe);
        } catch (ConflictHttpException) {
            throw ValidationException::withMessages([
                'base_version_id' => 'La receta cambió mientras la editabas. Conservamos tus cambios en el formulario; adopta la versión más reciente como base antes de guardar.',
            ]);
        }

        return to_route('recipes.show', $version->recipe_id, 303)->with('success', 'Receta guardada como nueva versión.');
    }

    private function formProps(?Recipe $recipe, ?array $cost = null): array
    {
        $version = $recipe?->latestVersion;

        return [
            'ingredients' => Ingredient::orderBy('name')->get(['id', 'name', 'canonical_unit']),
            'recipe' => $version ? $this->recipePayload($recipe, $version, $cost) : null,
            'requestKey' => (string) Str::uuid(),
            'storeUrl' => $recipe ? route('recipes.update', $recipe) : route('recipes.store'),
            'indexUrl' => route('recipes.index'),
            'backUrl' => $recipe ? route('recipes.show', $recipe) : route('recipes.index'),
        ];
    }

    private function recipePayload(Recipe $recipe, RecipeVersion $version, ?array $cost = null): array
    {
        $lines = $version->relationLoaded('lines') ? $version->lines : $version->lines()->with('ingredient')->get();

        return [
            'id' => $recipe->id,
            'version_id' => $version->id,
            'version_number' => $version->version_number,
            'name' => $version->name,
            'expected_yield' => $version->expected_yield,
            'instructions' => $version->instructions ?? '',
            'notes' => $version->notes ?? '',
            'image_url' => $version->image_path ? route('recipes.image', ['recipe' => $recipe, 'version' => $version]) : null,
            'snapshot_batch_cost_micros' => $version->snapshot_batch_cost_micros,
            'snapshot_unit_cost_micros' => $version->snapshot_unit_cost_micros,
            'lines' => $lines->map(fn ($line): array => [
                'ingredient_id' => $line->ingredient_id,
                'ingredient_name' => $line->ingredient?->name,
                'canonical_unit' => $line->ingredient?->canonical_unit,
                'quantity' => $this->decimal((string) $line->quantity_milli, 3),
                'unit' => $line->unit,
            ])->values()->all(),
            'cost' => $cost,
        ];
    }

    private function decimal(string $value, int $scale): string
    {
        $digits = str_pad($value, $scale + 1, '0', STR_PAD_LEFT);
        $whole = number_format((int) substr($digits, 0, -$scale), 0, '.', ',');
        $fraction = rtrim(substr($digits, -$scale), '0');

        return $fraction ? $whole.'.'.$fraction : $whole;
    }
}
