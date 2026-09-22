<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class RecoveryPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_admin === true;
    }

    public function rules(): array
    {
        return [
            'password' => ['required', 'string', 'min:12', 'confirmed'],
            'password_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.required' => 'Escribe una contraseña de recuperación.',
            'password.string' => 'La contraseña de recuperación debe ser texto.',
            'password.min' => 'La contraseña de recuperación debe tener al menos 12 caracteres.',
            'password.confirmed' => 'Las contraseñas de recuperación no coinciden.',
            'password_confirmation.required' => 'Confirma la contraseña de recuperación.',
            'password_confirmation.string' => 'La confirmación debe ser texto.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $password = $this->input('password');
            $confirmation = $this->input('password_confirmation');

            if (is_string($password) && is_string($confirmation) && $password !== '' && $confirmation !== '' && $password !== $confirmation) {
                $validator->errors()->add('password_confirmation', 'Las contraseñas de recuperación no coinciden.');
            }
        });
    }
}
