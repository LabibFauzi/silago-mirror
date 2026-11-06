<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MarketplaceDeal extends Model
{
    protected $fillable = [
        'conversation_id',
        'accepted_message_id',
        'product_id',
        'buyer_id',
        'seller_id',
        'quantity',
        'agreed_price',
        'payment_method',
        'status',
        'agreed_at',
        'completion_token_hash',
        'completion_code_hash',
        'completion_token_expires_at',
        'completion_attempts',
        'completion_locked_until',
        'completed_at',
        'completed_lat',
        'completed_lng',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'cancel_note',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'agreed_price' => 'decimal:2',
            'agreed_at' => 'datetime',
            'completion_token_expires_at' => 'datetime',
            'completion_attempts' => 'integer',
            'completion_locked_until' => 'datetime',
            'completed_at' => 'datetime',
            'completed_lat' => 'decimal:7',
            'completed_lng' => 'decimal:7',
            'cancelled_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function acceptedMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'accepted_message_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
