<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Source extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'website_url', 'logo', 'source_type',
        'reliability_score', 'active', 'last_success_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'reliability_score' => 'decimal:2',
            'last_success_at' => 'datetime',
        ];
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }
}

