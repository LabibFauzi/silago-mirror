<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|nullable|string|max:30',
            'profile_photo' => 'sometimes|nullable|image|max:2048|mimes:jpg,jpeg,png,webp',
        ];
    }
}
