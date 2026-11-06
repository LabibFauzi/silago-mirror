<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $otherParty = $this->buyer_id === $userId ? $this->seller : $this->buyer;

        return [
            'id' => $this->id,
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'cover_image_url' => $this->product?->cover_image_url, // Asumsi punya attribute/method ini
                'price' => $this->product?->price,
                'status' => $this->product?->status,
                'verification_status' => $this->product?->verification_status,
            ],
            'other_party' => [
                'id' => $otherParty?->id,
                'name' => $otherParty?->name,
                'profile_photo_url' => $otherParty?->profile_photo_url,
                'is_verified' => (bool) $otherParty?->email_verified_at, // Sesuaikan propertinya
            ],
            'last_message' => $this->lastMessage ? [
                'body' => Str::limit($this->lastMessage->message ?? ($this->lastMessage->type === 'offer' ? 'Penawaran' : 'Lokasi'), 50),
                'created_at' => $this->lastMessage->created_at?->diffForHumans(),
            ] : null,
            'unread_count' => $this->unread_count ?? 0,
            'updated_at' => $this->updated_at,
        ];
    }
}
