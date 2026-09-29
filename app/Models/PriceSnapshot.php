<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'offer_id', 'price', 'shipping_price', 'total_price',
        'availability', 'collected_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'shipping_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'availability' => 'boolean',
            'collected_at' => 'datetime',
        ];
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }
}

