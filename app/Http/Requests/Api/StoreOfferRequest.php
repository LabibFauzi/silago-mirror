<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreOfferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'price' => ['required', 'numeric', 'min:1'],
            'note' => ['nullable', 'string'],
            'parent_offer_id' => ['nullable', 'exists:messages,id'],
        ];
    }
}
