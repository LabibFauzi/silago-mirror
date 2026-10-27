<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $isParticipant = $userId !== null
            && ((int) $this->buyer_id === (int) $userId || (int) $this->seller_id === (int) $userId);
        $isBuyer = $userId !== null && (int) $this->buyer_id === (int) $userId;
        $isSeller = $userId !== null && (int) $this->seller_id === (int) $userId;
        $isAgreed = $this->status === 'agreed';
        $isCompleted = $this->status === 'completed';

        $hasReviewed = $isCompleted && $userId !== null
            ? $this->reviews()->where('reviewer_id', $userId)->exists()
            : false;

        $buyerHasReviewed = $isCompleted
            ? $this->reviews()->where('reviewer_id', $this->buyer_id)->exists()
            : false;

        $price = $this->agreed_price ?? $this->current_offer_price;

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'product' => [
                'id' => $this->product?->id,
                'name' => $this->product?->name,
                'cover_image' => $this->product?->image_url,
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
            'is_buyer' => $isBuyer,
            'is_seller' => $isSeller,
            'buyer_has_reviewed' => $buyerHasReviewed,
            'quantity' => $this->quantity,
            'agreed_price' => $this->agreed_price,
            'current_offer_price' => $this->current_offer_price,
            'total_price' => (float) $price * $this->quantity,
            'buyer_agreed' => (bool) $this->buyer_agreed,
            'seller_agreed' => (bool) $this->seller_agreed,
            'buyer_agreed_at' => $this->buyer_agreed_at,
            'seller_agreed_at' => $this->seller_agreed_at,
            'status' => $this->status,
            'agreed_at' => $this->agreed_at,
            'completed_at' => $this->completed_at,
            'cancelled_at' => $this->cancelled_at,
            'cancel_reason' => $this->cancel_reason,
            'cancel_note' => $this->cancel_note,
            'reported_at' => $this->reported_at,
            'has_reported' => $this->reported_at !== null,
            'created_at' => $this->created_at,
            'actions' => [
                'can_generate_qr' => $isSeller && $isAgreed,
                'can_complete' => $isBuyer && $isAgreed,
                'can_cancel' => $isParticipant && $isAgreed,
                'can_renegotiate' => $isParticipant && $isAgreed,
                // Ulasan hanya boleh diberikan oleh pembeli.
                'can_review' => $isCompleted && $isBuyer && ! $hasReviewed,
                'can_report' => $isParticipant && in_array($this->status, ['agreed', 'completed', 'cancelled'], true),
            ],
        ];
    }
}
