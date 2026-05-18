<?php

namespace App\Services;

use App\Models\CreditPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public const int TRANSLATION_ESTIMATED_CREDITS = 550;

    public const int STRESS_CORRECTION_ESTIMATED_CREDITS = 530;

    public function hasEnoughCredits(User $user, int $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        return $user->credits >= $amount;
    }

    /**
     * Atomically deducts $amount credits.
     * Returns false if balance is insufficient (no change applied).
     */
    public function deduct(User $user, int $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        $affected = DB::table('users')
            ->where('id', $user->id)
            ->where('credits', '>=', $amount)
            ->decrement('credits', $amount);

        return $affected > 0;
    }

    /**
     * Credits $amount credits in an atomic transaction.
     * Double idempotence guard: UPDATE WHERE status='pending' returns 0 if already completed.
     */
    public function credit(User $user, int $amount, CreditPurchase $purchase): void
    {
        DB::transaction(function () use ($user, $amount, $purchase): void {
            $affected = DB::table('credit_purchases')
                ->where('id', $purchase->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'completed',
                    'credited_at' => now(),
                ]);

            if ($affected === 0) {
                return;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->increment('credits', $amount);
        });
    }

    public function ttsCharsToCredits(int $chars): int
    {
        return $chars * config('credits.tts_credits_per_char', 10);
    }

    public function tokensToCredits(int $promptTokens, int $completionTokens): int
    {
        return $promptTokens + $completionTokens;
    }

    public function estimateCreditsForTts(int $estimatedChars): int
    {
        return $this->ttsCharsToCredits($estimatedChars);
    }
}
