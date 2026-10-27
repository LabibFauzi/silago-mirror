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
            'type' => ['required', 'string', 'in:text,location,image'],
            'message' => ['nullable', 'string', 'max:5000'],
            'image' => ['required_if:type,image', 'image', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:500'],
            'location_lat' => ['required_if:type,location', 'numeric'],
            'location_lng' => ['required_if:type,location', 'numeric'],
            'location_name' => ['nullable', 'string', 'max:150'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'location_accuracy' => ['nullable', 'integer'],
            'offer_price' => ['required_if:type,offer', 'numeric', 'min:1'],
            'offer_status' => ['nullable', 'string', 'in:pending,accepted,rejected,countered,withdrawn'],
            'parent_offer_id' => ['nullable', 'integer', 'exists:messages,id'],
        ];
    }
}
