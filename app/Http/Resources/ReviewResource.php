<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $deal = $this->marketplaceDeal;
        $product = $deal?->product;

        return [
            'id' => $this->id,
            'deal_id' => $this->marketplace_deal_id,
            'reviewer' => [
                'id' => $this->reviewer?->id,
                'name' => $this->reviewer?->name,
                'profile_photo_url' => $this->reviewer?->profile_photo_url,
            ],
            'reviewee' => [
                'id' => $this->reviewee?->id,
                'name' => $this->reviewee?->name,
                'profile_photo_url' => $this->reviewee?->profile_photo_url,
            ],
            'product' => $product === null ? null : [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
            ],
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
        ];
    }
}
