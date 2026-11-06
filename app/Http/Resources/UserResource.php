<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nim' => $this->nim,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_photo_url' => $this->profile_photo ? Storage::url($this->profile_photo) : null,
            'role' => $this->role,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'two_factor_enabled' => $this->two_factor_enabled,
            'email_domain_verified' => $this->email_domain_verified,
            'avatar_url' => $this->avatar ?: ($this->profile_photo ? Storage::url($this->profile_photo) : null),
            'created_at' => $this->created_at,
        ];
    }
}
