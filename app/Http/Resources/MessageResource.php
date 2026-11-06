<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'conversation_id' => $this->conversation_id,
            'sender_id' => $this->sender_id,
            'type' => $this->type,
            'message' => $this->message,
            'offer_price' => $this->offer_price,
            'offer_status' => $this->offer_status,
            'parent_offer_id' => $this->parent_offer_id,
            'location_lat' => $this->location_lat,
            'location_lng' => $this->location_lng,
            'location_name' => $this->location_name,
            'location_address' => $this->location_address,
            'location_accuracy' => $this->location_accuracy,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
