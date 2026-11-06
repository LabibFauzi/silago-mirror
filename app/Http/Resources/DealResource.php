<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'cover_image' => $this->product?->cover_image_url,
                'price' => $this->product?->price,
            ],
            'buyer' => [
                'id' => $this->buyer?->id,
                'name' => $this->buyer?->name,
            ],
            'seller' => [
                'id' => $this->seller?->id,
                'name' => $this->seller?->name,
            ],
            'quantity' => $this->quantity,
            'agreed_price' => $this->agreed_price,
            'total_price' => $this->agreed_price * $this->quantity,
            'status' => $this->status,
            'agreed_at' => $this->agreed_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'cancel_note' => $this->cancel_note,
            'created_at' => $this->created_at,
        ];
    }
}
