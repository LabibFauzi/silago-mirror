<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodPoint extends Model
{
    protected $fillable = [
        'name',
        'address',
        'lat',
        'lng',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'lat' => 'decimal:7',
            'lng' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }
}
