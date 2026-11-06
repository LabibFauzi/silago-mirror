<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

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
            'is_read' => $this->is_read,
            'created_at' => Carbon::parse($this->created_at)->setTimezone('Asia/Jakarta')->isoFormat('D MMM YYYY, HH:mm'),
        ];
    }
}
