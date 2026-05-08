<?php

namespace App\Models;

use Database\Factories\ApiUsageLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
