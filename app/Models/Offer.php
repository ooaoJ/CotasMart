<?php

namespace App\Models;

use App\Observers\OfferObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([OfferObserver::class])]
class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'source_id', 'external_identifier', 'title',
        'seller', 'url', 'current_price', 'shipping_price',
        'installment_price', 'payment_condition', 'availability',
        'last_checked_at',
    ];

    protected function casts(): array
    {
        return [
            'current_price' => 'decimal:2',
            'shipping_price' => 'decimal:2',
            'installment_price' => 'decimal:2',
            'availability' => 'boolean',
            'last_checked_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(Source::class);
    }

    public function priceSnapshots(): HasMany
    {
        return $this->hasMany(PriceSnapshot::class);
    }
}

