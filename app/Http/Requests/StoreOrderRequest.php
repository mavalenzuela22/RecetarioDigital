<?php

namespace App\Http\Requests;

use App\Services\ProductCosting;
use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['bail', 'required', 'string', 'max:120'],
            'lines' => ['bail', 'required', 'array', 'min:1', 'max:100'],
            'lines.*.product_id' => ['bail', 'required', 'integer', 'exists:products,id'],
            'lines.*.quantity' => ['bail', 'required', 'regex:/^[1-9][0-9]{0,8}$/'],
            'lines.*.agreed_price' => ['bail', 'required', function (string $attribute, mixed $value, \Closure $fail): void {
                try {
                    ProductCosting::minor($value, $attribute, true);
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $fail($exception->errors()[$attribute][0] ?? 'Escribe un precio acordado mayor que $0.');
                }
            }],
            'delivery_date' => ['bail', 'required', 'date_format:Y-m-d'],
            'delivery_time' => ['bail', 'required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'advance' => ['nullable', function (string $attribute, mixed $value, \Closure $fail): void {
                try {
                    ProductCosting::minor($value ?? '0', $attribute);
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $fail($exception->errors()[$attribute][0] ?? 'Escribe un anticipo válido.');
                }
            }],
            'request_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.',
            'string' => 'Escribe texto en este campo.',
            'max' => 'Usa un máximo de :max caracteres.',
            'array' => 'Agrega al menos un producto.',
            'min' => 'Agrega al menos un producto.',
            'integer' => 'Elige un producto válido.',
            'exists' => 'Elige un producto existente.',
            'regex' => 'Escribe una cantidad entera mayor que cero.',
            'date_format' => 'Escribe una fecha u hora válida.',
            'uuid' => 'No pudimos identificar este pedido. Abre una nueva captura.',
        ];
    }
}
