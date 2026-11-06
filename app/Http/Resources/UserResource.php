<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isSelf = $request->user()?->id === $this->id;
        $hasPhone = ! empty($this->phone);
        // Nomor hanya tampil untuk diri sendiri, atau ke orang lain bila
        // pengguna mengaktifkan "tampilkan nomor".
        $canRevealPhone = $isSelf || ($hasPhone && (bool) $this->show_phone);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'nim' => $this->nim,
            'email' => $this->email,
            'phone' => $canRevealPhone ? $this->phone : null,
            'has_phone' => $hasPhone,
            'show_phone' => (bool) $this->show_phone,
            'profile_photo_url' => $this->profile_photo ? Storage::url($this->profile_photo) : null,
            'role' => $this->role,
            'status' => $this->status,
            'email_verified_at' => $this->email_verified_at,
            'two_factor_enabled' => $this->two_factor_enabled,
            'email_domain_verified' => $this->email_domain_verified,
            'avatar_url' => $this->profile_photo ? Storage::url($this->profile_photo) : $this->avatar,
            'created_at' => $this->created_at,
        ];
    }
}
