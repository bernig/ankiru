<?php

namespace App\Models;

use Database\Factories\CreditPurchaseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditPurchase extends Model
{
    /** @use HasFactory<CreditPurchaseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'pack_slug',
        'credits',
        'amount_cents',
        'currency',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'status',
        'credited_at',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'credited_at' => 'datetime',
            'credits' => 'integer',
            'amount_cents' => 'integer',
        ];
    }
}
