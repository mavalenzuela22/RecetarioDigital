<?php

namespace App\Http\Requests;

use App\Services\RecordIngredientPurchase;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class StoreIngredientPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $decimal = fn (int $scale) => function ($attribute, $value, $fail) use ($scale): void {
            try {
                RecordIngredientPurchase::decimal($value, $scale, $attribute);
            } catch (ValidationException $exception) {
                $fail($exception->errors()[$attribute][0]);
            }
        };

        return [
            'ingredient_name' => ['bail', 'required', 'string', 'max:120'],
            'presentation' => ['bail', 'required', 'string', 'max:120'],
            'purchase_quantity' => ['bail', 'required', $decimal(3)],
            'purchase_unit' => ['required', Rule::in(array_keys(RecordIngredientPurchase::UNITS))],
            'total_paid' => ['bail', 'required', $decimal(2)],
            'purchased_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:1000-01-01', 'before_or_equal:9999-12-31'],
            'store' => ['nullable', 'string', 'max:120'],
            'note' => ['nullable', 'string', 'max:2000'],
            'request_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.', 'string' => 'Escribe texto en este campo.',
            'max' => 'Usa un máximo de :max caracteres.', 'in' => 'Elige una unidad válida.',
            'date_format' => 'Escribe una fecha válida.',
            'after_or_equal' => 'La fecha debe ser del año 1000 en adelante.',
            'before_or_equal' => 'La fecha debe ser anterior al año 10000.',
            'uuid' => 'No pudimos identificar esta compra. Abre una nueva compra e inténtalo de nuevo.',
        ];
    }
}
