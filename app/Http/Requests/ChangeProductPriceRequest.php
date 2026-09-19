<?php

namespace App\Http\Requests;

use App\Services\ProductCosting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeProductPriceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'mode' => ['required', Rule::in(['scenario', 'manual'])],
            'scenario' => ['nullable', Rule::in(array_keys(ProductCosting::SCENARIOS))],
            'price' => ['nullable', 'required_if:mode,manual', function (string $attribute, mixed $value, \Closure $fail): void {
                try {
                    ProductCosting::minor($value, $attribute, true);
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $fail($exception->errors()[$attribute][0] ?? 'Escribe un precio mayor que $0.');
                }
            }],
            'confirmed' => ['accepted'],
            'request_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.',
            'required_if' => 'Escribe el precio manual.',
            'in' => 'Elige un escenario válido.',
            'accepted' => 'Confirma la aplicación del nuevo precio.',
            'uuid' => 'No pudimos identificar este cambio de precio. Abre una nueva captura.',
        ];
    }
}
