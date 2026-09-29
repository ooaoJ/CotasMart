<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Search extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'user_id',
        'product_id',
        'query',
        'normalized_query',
        'status',
        'filters',
        'results_count',
        'error_message',
        'searched_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'results_count' => 'integer',
            'searched_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'error_message' => null,
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    public function markAsCompleted(int $resultsCount): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'results_count' => $resultsCount,
            'error_message' => null,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $message,
            'completed_at' => now(),
        ]);
    }
}