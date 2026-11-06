<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:text,location'],
            'message' => ['required_if:type,text', 'string', 'max:5000'],
            'location_lat' => ['required_if:type,location', 'numeric'],
            'location_lng' => ['required_if:type,location', 'numeric'],
            'location_name' => ['nullable', 'string', 'max:150'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'location_accuracy' => ['nullable', 'integer'],
        ];
    }
}
