<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    protected $fillable = [
        'seller_id',
        'category_id',
        'name',
        'description',
        'condition',
        'specification',
        'completeness',
        'sell_reason',
        'price',
        'stock',
        'status',
        'verification_status',
        'verified_by',
        'verified_at',
        'rejection_note',
        'scan_result',
        'scan_note',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'stock' => 'integer',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    protected $appends = [
        'image_url',
        'image_urls',
    ];

    public function getImageUrlAttribute(): ?string
    {
        $firstImage = $this->images->sortBy('sort_order')->first();
        if (!$firstImage || !$firstImage->image_path) {
            return null;
        }
        $path = $firstImage->image_path;
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return '/storage/' . ltrim($path, '/');
    }

    /**
     * Semua foto produk dalam bentuk URL publik (untuk galeri/preview).
     *
     * @return array<int, string>
     */
    public function getImageUrlsAttribute(): array
    {
        return $this->images
            ->sortBy('sort_order')
            ->map(function ($image) {
                $path = $image->image_path;
                if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
                    return $path;
                }
                return Storage::url($path);
            })
            ->values()
            ->all();
    }
}
