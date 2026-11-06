<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (!$this->has('login')) {
            if ($this->has('email')) {
                $this->merge(['login' => $this->email]);
            } elseif ($this->has('nim')) {
                $this->merge(['login' => $this->nim]);
            } elseif ($this->has('username')) {
                $this->merge(['login' => $this->username]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'login' => 'required|string',
            'password' => 'required|string',
            'fcm_token' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'login.required' => 'Email atau NIM wajib diisi.',
            'password.required' => 'Kata sandi wajib diisi.',
        ];
    }
}
