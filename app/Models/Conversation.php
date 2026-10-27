<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    protected $fillable = [
        'product_id',
        'buyer_id',
        'seller_id',
        'type',
    ];

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

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function marketplaceDeals(): HasMany
    {
        return $this->hasMany(MarketplaceDeal::class);
    }

    public function latestDeal(): HasOne
    {
        return $this->hasOne(MarketplaceDeal::class)->latestOfMany();
    }

    public function latestOffer(): HasOne
    {
        return $this->hasOne(Message::class)
            ->ofMany(['id' => 'max'], function (Builder $query) {
                $query->where('type', 'offer');
            });
    }
}
