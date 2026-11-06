<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reviewer' => [
                'id' => $this->reviewer?->id,
                'name' => $this->reviewer?->name,
                'profile_photo_url' => $this->reviewer?->profile_photo_url,
            ],
            'rating' => $this->rating,
            'comment' => $this->comment,
            'created_at' => $this->created_at,
        ];
    }
}
