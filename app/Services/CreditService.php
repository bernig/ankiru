<?php

namespace App\Services;

use App\Enums\UsageOperation;
use App\Models\ApiUsageLog;
use App\Models\CreditPurchase;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreditService
{
    public const int TRANSLATION_ESTIMATED_CREDITS = 550;

    public const int STRESS_CORRECTION_ESTIMATED_CREDITS = 530;

    /**
     * Whether an AI action estimated at $estimatedCredits may start. Users on
     * their own OpenAI key pay OpenAI directly, so the balance never blocks them.
     */
    public function canAfford(User $user, int $estimatedCredits): bool
    {
        return ! $user->usesPlatformCredits() || $this->hasEnoughCredits($user, $estimatedCredits);
    }

    /**
     * Log a paid chat completion and, on platform credits, charge its tokens.
     *
     * @param  bool|null  $billCredits  Billing mode to apply; defaults to the user's
     *                                  current one. Queued jobs pass the mode frozen at dispatch.
     */
    public function recordTokenUsage(User $user, UsageOperation $operation, int $promptTokens, int $completionTokens, ?bool $billCredits = null): void
    {
        ApiUsageLog::create([
            'user_id' => $user->id,
            'operation' => $operation,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
        ]);

        if ($billCredits ?? $user->usesPlatformCredits()) {
            $this->deduct($user, $this->tokensToCredits($promptTokens, $completionTokens));
        }
    }

    /**
     * Log a newly generated TTS file and, on platform credits, charge its characters.
     *
     * @param  bool|null  $billCredits  See recordTokenUsage().
     */
    public function recordTtsUsage(User $user, int $characters, ?bool $billCredits = null): void
    {
        ApiUsageLog::create([
            'user_id' => $user->id,
            'operation' => UsageOperation::Tts,
            'characters' => $characters,
        ]);

        if ($billCredits ?? $user->usesPlatformCredits()) {
            $this->deduct($user, $this->ttsCharsToCredits($characters));
        }
    }

    public function hasEnoughCredits(User $user, int $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        return $user->credits >= $amount;
    }

    /**
     * Atomically deducts $amount credits, flooring the balance at 0.
     *
     * Deductions happen after the AI call has been paid for, so a shortfall is
     * still charged down to 0 instead of being skipped. Returns false when the
     * balance did not cover the full amount.
     */
    public function deduct(User $user, int $amount): bool
    {
        if ($amount <= 0) {
            return true;
        }

        return DB::transaction(function () use ($user, $amount): bool {
            $balance = (int) DB::table('users')->where('id', $user->id)->lockForUpdate()->value('credits');
            $newBalance = max(0, $balance - $amount);

            DB::table('users')->where('id', $user->id)->update(['credits' => $newBalance]);

            $user->forceFill(['credits' => $newBalance])->syncOriginalAttribute('credits');

            return $balance >= $amount;
        });
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
