<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'nim' => 'required|string|max:20|unique:users|regex:/^[0-9.]+$/',
            'email' => ['required', 'email', 'unique:users', function ($attribute, $value, $fail) {
                $domain = strtolower(substr(strrchr($value, '@'), 1));
                if ($domain !== 'mhs.polines.ac.id') {
                    $fail('Email harus menggunakan domain mahasiswa (@mhs.polines.ac.id).');
                }
            }],
            'password' => 'required|string|min:8|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'nim.required' => 'NIM wajib diisi.',
            'nim.unique' => 'NIM sudah terdaftar.',
            'nim.regex' => 'Format NIM hanya boleh berisi angka dan titik (contoh: 3.34.25.1.14 atau 33425114).',
            'email.required' => 'Email mahasiswa wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok. Pastikan mengisi field password_confirmation yang sama dengan password.',
        ];
    }
}
