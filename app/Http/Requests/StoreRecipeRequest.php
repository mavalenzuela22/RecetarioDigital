<?php

namespace App\Http\Requests;

use App\Services\SaveRecipe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:120'],
            'expected_yield' => ['bail', 'required', 'regex:/^[1-9][0-9]{0,8}$/'],
            'instructions' => ['nullable', 'string', 'max:10000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'base_version_id' => ['nullable', 'integer', 'exists:recipe_versions,id'],
            'request_key' => ['required', 'uuid'],
            'ingredients' => ['bail', 'required', 'array', 'min:1', 'max:100'],
            'ingredients.*.ingredient_id' => ['bail', 'required', 'integer', 'exists:ingredients,id'],
            'ingredients.*.quantity' => ['bail', 'required', function (string $attribute, mixed $value, \Closure $fail): void {
                try {
                    SaveRecipe::quantity($value, $attribute);
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $fail($exception->errors()[$attribute][0] ?? 'Escribe una cantidad válida.');
                }
            }],
            'ingredients.*.unit' => ['required', Rule::in(array_keys(SaveRecipe::UNITS))],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.',
            'max' => 'Usa un máximo de :max caracteres.',
            'regex' => 'Escribe un número entero mayor que cero.',
            'array' => 'Agrega al menos un ingrediente.',
            'min' => 'Agrega al menos un ingrediente.',
            'exists' => 'Elige un ingrediente existente.',
            'in' => 'Elige una unidad válida.',
            'integer' => 'Elige un ingrediente válido.',
            'uuid' => 'No pudimos identificar esta receta. Abre una nueva captura e inténtalo de nuevo.',
            'mimes' => 'Sube una imagen JPEG, PNG o WebP.',
            'file' => 'No pudimos leer esta imagen.',
        ];
    }
}
