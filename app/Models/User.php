<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'nim',
        'email',
        'phone',
        'password',
        'role',
        'status',
        'email_verified_at',
        'profile_photo',
        'suspend_reason',
        'suspended_at',
        'google_id',
        'avatar',
        'otp_code',
        'otp_expires_at',
        'email_domain_verified',
        'two_factor_enabled',
    ];

    /**
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'fcm_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'suspended_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'email_domain_verified' => 'boolean',
            'two_factor_enabled' => 'boolean',
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class, 'reported_user_id');
    }

    public function marketplaceDeals(): HasMany
    {
        return $this->hasMany(MarketplaceDeal::class, 'seller_id');
    }

    protected $appends = [
        'avatar_url',
        'profile_photo_url',
    ];

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (!$this->profile_photo) {
            return null;
        }

        if (str_starts_with($this->profile_photo, 'http://') || str_starts_with($this->profile_photo, 'https://')) {
            return $this->profile_photo;
        }

        return '/storage/' . ltrim($this->profile_photo, '/');
    }

    public function getAvatarUrlAttribute(): string
    {
        if ($this->profile_photo) {
            return $this->profile_photo_url ?? 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?: 'User');
        }

        if ($this->avatar) {
            if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
                return $this->avatar;
            }
            return '/storage/' . ltrim($this->avatar, '/');
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name ?: 'User');
    }
}
