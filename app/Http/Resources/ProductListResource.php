<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductListResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => (float) $this->price,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            'condition' => $this->condition,
            'created_at' => $this->created_at,
            
            'cover_image' => $this->whenLoaded('images', function () {
                $cover = $this->images->where('sort_order', 0)->first() 
                         ?? $this->images->first(); // fallback jika tidak ada sort_order 0
                         
                return $cover ? Storage::url($cover->image_path) : null;
            }),

            // Semua foto produk (untuk carousel/slide di aplikasi mobile).
            'images' => $this->whenLoaded('images', function () {
                return $this->images
                    ->sortBy('sort_order')
                    ->values()
                    ->map(function ($image) {
                        return [
                            'id' => $image->id,
                            'url' => Storage::url($image->image_path),
                            'sort_order' => $image->sort_order,
                        ];
                    });
            }),
            
            'seller' => $this->whenLoaded('seller', function () {
                return [
                    'id' => $this->seller->id,
                    'name' => $this->seller->name,
                    'profile_photo_url' => $this->seller->profile_photo_url,
                ];
            }),
            
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            
            'is_favorited' => $this->when($user !== null, function () use ($user) {
                if ($this->relationLoaded('favorites')) {
                    return $this->favorites->contains('user_id', $user->id);
                }
                return $this->favorites()->where('user_id', $user->id)->exists();
            }, false),
        ];
    }
}
