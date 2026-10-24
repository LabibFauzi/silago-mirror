<?php

namespace App\Http\Requests\Api;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Tentukan apakah user berhak melakukan request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aturan validasi untuk request.
     */
    public function rules(SettingService $settingService): array
    {
        $maxPhotos = $settingService->getInt('max_photos', 5);

        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'condition' => ['required', 'string', 'in:Baru (BNIB),Baru,Bekas Layak,Bekas'],
            'specification' => ['nullable', 'string'],
            'completeness' => ['nullable', 'string', 'max:255'],
            'sell_reason' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:1'],
            'stock' => ['required', 'integer', 'min:1'],
            'category_id' => ['required', 'exists:categories,id'],
            'photos' => ['required', 'array', 'min:1', 'max:' . $maxPhotos],
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }
}
