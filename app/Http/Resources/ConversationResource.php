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
        $isBuyer = $this->buyer_id === $userId;
        $isSeller = $this->seller_id === $userId;
        $otherParty = $isBuyer ? $this->seller : $this->buyer;

        $deal = $this->relationLoaded('latestDeal')
            ? $this->latestDeal
            : $this->latestDeal()->first();

        $offer = $this->relationLoaded('latestOffer')
            ? $this->latestOffer
            : $this->latestOffer()->first();

        $hasPendingOffer = $offer && $offer->offer_status === 'pending';
        $stage = 'chat';
        if ($deal) {
            $stage = match ($deal->status) {
                'agreed' => 'deal',
                'completed' => 'completed',
                // Deal batal boleh diikuti negosiasi baru.
                'cancelled' => $hasPendingOffer ? 'negotiation' : 'cancelled',
                default => 'deal',
            };
        } elseif ($hasPendingOffer) {
            $stage = 'negotiation';
        }

        $lastMessage = $this->lastMessage;
        $lastBody = null;
        if ($lastMessage) {
            $lastBody = $lastMessage->message;
            if (! $lastBody) {
                $lastBody = match ($lastMessage->type) {
                    'offer' => 'Penawaran Rp' . number_format((float) $lastMessage->offer_price, 0, ',', '.'),
                    'location' => 'Lokasi dibagikan',
                    default => '',
                };
            }
        }

        return [
            'id' => $this->id,
            'buyer_id' => $this->buyer_id,
            'seller_id' => $this->seller_id,
            'role' => $userId ? ($isBuyer ? 'buyer' : ($isSeller ? 'seller' : null)) : null,
            'stage' => $stage,
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'cover_image' => $this->product?->image_url,
                'price' => (float) ($this->product?->price ?? 0),
                'status' => $this->product?->status,
                'verification_status' => $this->product?->verification_status,
            ],
            'other_party' => [
                'id' => $otherParty?->id,
                'name' => $otherParty?->name,
                'profile_photo_url' => $otherParty?->profile_photo_url,
                'avatar_url' => $otherParty?->avatar_url,
                'is_verified' => (bool) $otherParty?->email_verified_at,
            ],
            'last_message' => $lastMessage ? [
                'body' => Str::limit($lastBody, 50),
                'type' => $lastMessage->type,
                'sender_id' => $lastMessage->sender_id,
                'created_at' => $lastMessage->created_at?->toIso8601String(),
            ] : null,
            'unread_count' => (int) ($this->unread_count ?? 0),
            'updated_at' => $this->updated_at,
        ];
    }
}
