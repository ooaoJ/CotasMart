<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'model', 'gtin',
        'description', 'specifications', 'image', 'popularity_score',
        'search_count', 'last_searched_at', 'last_updated_at',
        'next_update_at', 'status',
    ];

    protected $casts = [
        'specifications' => 'array',
        'popularity_score' => 'integer',
        'search_count' => 'integer',
        'last_searched_at' => 'datetime',
        'last_updated_at' => 'datetime',
        'next_update_at' => 'datetime',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function searches(): HasMany
    {
        return $this->hasMany(Search::class);
    }
}
