<?php

namespace App\Models;

use Database\Factories\CsvDraftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CsvDraft extends Model
{
    /** @use HasFactory<CsvDraftFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'original_file_name',
        'csv_rows',
        'has_csv_loaded',
        'last_accessed_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'csv_rows' => 'array',
            'has_csv_loaded' => 'boolean',
            'last_accessed_at' => 'datetime',
        ];
    }
}
