<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'conversation_id',
        'sender_id',
        'type',
        'message',
        'offer_price',
        'offer_status',
        'parent_offer_id',
        'location_lat',
        'location_lng',
        'location_name',
        'location_address',
        'location_accuracy',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'offer_price' => 'decimal:2',
            'location_lat' => 'decimal:7',
            'location_lng' => 'decimal:7',
            'location_accuracy' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function parentOffer(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_offer_id');
    }
}
