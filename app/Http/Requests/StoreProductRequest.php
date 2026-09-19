<?php

namespace App\Http\Requests;

use App\Services\ProductCosting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'recipe_id' => ['sometimes', 'required', 'integer', 'exists:recipes,id'],
            'base_profile_id' => ['nullable', 'integer', 'exists:product_cost_profiles,id'],
            'active' => ['required', 'boolean'],
            'reference_order_quantity' => ['nullable', 'regex:/^[1-9][0-9]{0,8}$/'],
            'request_key' => ['required', 'uuid'],
            'components' => ['nullable', 'array', 'max:50'],
            'components.*.concept' => ['bail', 'required', 'string', 'max:120'],
            'components.*.amount' => ['bail', 'required', function (string $attribute, mixed $value, \Closure $fail): void {
                try {
                    ProductCosting::minor($value, $attribute);
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $fail($exception->errors()[$attribute][0] ?? 'Escribe un importe válido.');
                }
            }],
            'components.*.allocation' => ['required', Rule::in(['batch', 'unit', 'order'])],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.',
            'exists' => 'Elige una receta existente.',
            'boolean' => 'Elige si el producto está activo.',
            'regex' => 'Escribe una cantidad entera mayor que cero.',
            'array' => 'Revisa los costos adicionales.',
            'max' => 'Usa un máximo de :max caracteres o elementos.',
            'in' => 'Elige una asignación válida.',
            'uuid' => 'No pudimos identificar esta configuración. Abre una nueva captura.',
        ];
    }
}
