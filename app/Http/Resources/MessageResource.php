<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isDeleted = $this->deleted_at !== null;
        $replyTo = $this->relationLoaded('replyTo') ? $this->replyTo : null;

        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'type' => $isDeleted ? 'text' : $this->type,
            'message' => $isDeleted ? null : $this->message,
            'offer_price' => $isDeleted ? null : $this->offer_price,
            'offer_status' => $this->offer_status,
            'parent_offer_id' => $this->parent_offer_id,
            'reply_to_id' => $this->reply_to_id,
            'reply_to' => $replyTo && $replyTo->deleted_at === null
                ? [
                    'id' => $replyTo->id,
                    'sender_id' => $replyTo->sender_id,
                    'type' => $replyTo->type,
                    'message' => $replyTo->message,
                    'image_thumbnail' => $replyTo->image_thumbnail,
                    'location_name' => $replyTo->location_name,
                ]
                : null,
            'location_lat' => $isDeleted ? null : $this->location_lat,
            'location_lng' => $isDeleted ? null : $this->location_lng,
            'location_name' => $isDeleted ? null : $this->location_name,
            'location_address' => $isDeleted ? null : $this->location_address,
            'location_accuracy' => $this->location_accuracy,
            'image_path' => $isDeleted ? null : $this->image_url,
            'image_thumbnail' => $isDeleted ? null : $this->image_thumbnail,
            'image_caption' => $isDeleted ? null : $this->image_caption,
            'is_deleted' => $isDeleted,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
