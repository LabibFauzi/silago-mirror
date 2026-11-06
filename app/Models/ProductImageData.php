<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImageData extends Model
{
    protected $table = 'product_image_data';

    public $timestamps = false;

    protected $fillable = [
        'product_image_id',
        'data',
    ];

    /**
     * Jangan pernah kirim base64 gambar lewat JSON API.
     */
    protected $hidden = [
        'data',
    ];

    public function image(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'product_image_id');
    }
}
