<?php

namespace App\Http\Requests;

use App\Services\ProductCosting;
use Illuminate\Foundation\Http\FormRequest;

class RecordOrderPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['bail', 'required', function (string $attribute, mixed $value, \Closure $fail): void {
                try {
                    ProductCosting::minor($value, $attribute, true);
                } catch (\Illuminate\Validation\ValidationException $exception) {
                    $fail('Escribe un importe mayor que $0 y no mayor que el saldo.');
                }
            }],
            'payment_date' => ['bail', 'required', 'date_format:Y-m-d'],
            'request_key' => ['required', 'uuid'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Completa este campo.',
            'date_format' => 'Escribe una fecha válida.',
            'uuid' => 'No pudimos identificar este cobro. Abre el formulario de nuevo.',
        ];
    }
}
