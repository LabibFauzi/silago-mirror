<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'title',
        'body',
        'type',
        'reference_id',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'is_read' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
