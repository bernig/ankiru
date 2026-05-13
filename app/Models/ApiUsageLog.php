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

    protected static function booted(): void
    {
        static::created(function (ApiUsageLog $log): void {
            Cache::forget("user_usage_stats:{$log->user_id}");
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
