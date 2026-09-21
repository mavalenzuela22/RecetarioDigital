<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BootstrapSecretRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'secret' => ['required', 'string', 'max:512'],
        ];
    }
}
