<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    /**
     * Transform resource ke dalam array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $user = $request->user();
        $isOwner = $user && $user->id === $this->seller_id;
        $isStaff = $user && $user->role === 'staff'; // Asumsi role staff

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'condition' => $this->condition,
            'specification' => $this->specification,
            'completeness' => $this->completeness,
            'sell_reason' => $this->sell_reason,
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'status' => $this->status,
            'verification_status' => $this->verification_status,
            
            // Rejection note hanya untuk pemilik barang
            'rejection_note' => $this->when($isOwner, $this->rejection_note),
            
            // Scan result & note hanya untuk staf
            'scan_result' => $this->when($isStaff, $this->scan_result),
            'scan_note' => $this->when($isStaff, $this->scan_note),
            
            'expires_at' => $this->expires_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            'seller' => $this->whenLoaded('seller', function () {
                return [
                    'id' => $this->seller->id,
                    'name' => $this->seller->name,
                    'profile_photo_url' => $this->seller->profile_photo_url,
                    'is_verified' => (bool) $this->seller->email_verified_at,
                ];
            }),
            
            'category' => $this->whenLoaded('category', function () {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                    'icon' => $this->category->icon,
                ];
            }),
            
            'images' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return [
                        'id' => $image->id,
                        'url' => Storage::url($image->image_path),
                        'sort_order' => $image->sort_order,
                    ];
                });
            }),
            
            'is_favorited' => $this->when($user !== null, function () use ($user) {
                // Cek dari relasi yang di-load atau query db
                if ($this->relationLoaded('favorites')) {
                    return $this->favorites->contains('user_id', $user->id);
                }
                return $this->favorites()->where('user_id', $user->id)->exists();
            }, false),
        ];
    }
}
