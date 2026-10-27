<?php

namespace App\Http\Requests\Api;

use App\Services\SettingService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'description' => ['sometimes', 'nullable', 'string'],
            'condition' => ['sometimes', 'required', 'string', 'in:Baru (BNIB),Baru,Bekas Layak,Bekas'],
            'specification' => ['sometimes', 'nullable', 'string'],
            'completeness' => ['sometimes', 'nullable', 'string', 'max:255'],
            'sell_reason' => ['sometimes', 'nullable', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'min:1'],
            'category_id' => ['sometimes', 'required', 'exists:categories,id'],
            'photos' => ['sometimes', 'array', 'min:1', 'max:' . $maxPhotos],
            // Maksimal 1 MB per foto.
            'photos.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:1024'],
        ];
    }
}
