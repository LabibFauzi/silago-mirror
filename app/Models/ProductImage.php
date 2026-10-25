<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductImage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'image_path',
        'has_data',
        'mime_type',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'has_data' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function data(): HasOne
    {
        return $this->hasOne(ProductImageData::class, 'product_image_id');
    }

    protected $appends = ['url'];

    public function getUrlAttribute(): ?string
    {
        // Gambar baru disimpan di database dan disajikan lewat route media.
        if ($this->has_data) {
            return '/media/product-images/' . $this->id;
        }

        if (!$this->image_path) {
            return null;
        }

        if (str_starts_with($this->image_path, 'http://') || str_starts_with($this->image_path, 'https://')) {
            return $this->image_path;
        }

        return '/storage/' . ltrim($this->image_path, '/');
    }
}
