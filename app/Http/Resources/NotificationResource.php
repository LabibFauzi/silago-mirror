<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'reference_id' => $this->reference_id,
            'data' => $this->data ?? [],
            'is_read' => (bool) $this->is_read,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_at_label' => $this->created_at?->timezone('Asia/Jakarta')->isoFormat('D MMM YYYY, HH:mm'),
        ];
    }
}
