<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $isBuyer = $userId !== null && (int) $this->buyer_id === (int) $userId;
        $otherParty = $isBuyer ? $this->seller : $this->buyer;

        $deal = $this->relationLoaded('latestDeal')
            ? $this->latestDeal
            : $this->latestDeal()->first();
        $offer = $this->relationLoaded('latestOffer')
            ? $this->latestOffer
            : $this->latestOffer()->first();

        $stage = 'chat';
        if ($deal) {
            $stage = match ($deal->status) {
                'negotiating' => 'negotiation',
                'agreed' => 'deal',
                'completed' => 'completed',
                'cancelled' => 'cancelled',
                default => 'chat',
            };
        }

        // Penawaran baru setelah deal dibatalkan membuka nego lagi.
        if ($offer?->offer_status === 'pending' && $deal?->status !== 'completed') {
            $stage = 'negotiation';
        }

        $last = $this->lastMessage;
        $lastBody = '';
        if ($last) {
            $lastBody = match ($last->type) {
                'offer' => $last->offer_price
                    ? 'Penawaran Rp'.number_format((float) $last->offer_price, 0, ',', '.')
                    : 'Penawaran',
                'location' => 'Lokasi dibagikan',
                'image' => $last->image_caption ?: 'Foto',
                'system' => (string) $last->message,
                default => (string) ($last->message ?? ''),
            };
        }

        return [
            'id' => $this->id,
            'buyer_id' => $this->buyer_id,
            'seller_id' => $this->seller_id,
            'role' => $isBuyer ? 'buyer' : 'seller',
            'type' => $this->type ?? 'chat',
            'stage' => $stage,
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'cover_image' => $this->product?->image_url,
                'price' => $this->product?->price,
                'status' => $this->product?->status,
                'verification_status' => $this->product?->verification_status,
            ],
            'other_party' => [
                'id' => $otherParty?->id,
                'name' => $otherParty?->name,
                'profile_photo_url' => $otherParty?->profile_photo_url,
                'is_verified' => (bool) $otherParty?->email_verified_at,
            ],
            'last_message' => $last ? [
                'body' => $lastBody,
                'type' => $last->type,
                'sender_id' => $last->sender_id,
                'created_at' => $last->created_at?->toIso8601String(),
            ] : null,
            'unread_count' => $this->unread_count ?? 0,
            'updated_at' => $this->updated_at,
        ];
    }
}
