<?php

namespace App\Models;

use Database\Factories\ApiUsageLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class ApiUsageLog extends Model
{
    /** @use HasFactory<ApiUsageLogFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'operation',
        'prompt_tokens',
        'completion_tokens',
        'characters',
    ];

    /**
     * Cache key of a user's usage statistics, shown on the API key page and
     * invalidated by every new log.
     */
    public static function usageStatsCacheKey(int $userId): string
    {
        return "user_usage_stats:{$userId}";
    }

    protected static function booted(): void
    {
        static::created(function (ApiUsageLog $log): void {
            Cache::forget(self::usageStatsCacheKey($log->user_id));
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
