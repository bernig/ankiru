<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CardReview extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'csv_draft_id',
        'row_index',
        'repetitions',
        'ease_factor',
        'interval_days',
        'due_date',
        'last_reviewed_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function csvDraft(): BelongsTo
    {
        return $this->belongsTo(CsvDraft::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'last_reviewed_at' => 'datetime',
            'ease_factor' => 'decimal:2',
        ];
    }
}
