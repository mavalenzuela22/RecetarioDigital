<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransitionOrderFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['request_key' => ['required', 'uuid']];
    }

    public function messages(): array
    {
        return [
            'required' => 'No pudimos identificar esta acción. Abre la pantalla de nuevo.',
            'uuid' => 'No pudimos identificar esta acción. Abre la pantalla de nuevo.',
        ];
    }
}
