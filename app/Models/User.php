<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'openai_api_key',
        'credits',
        'accent_color',
        'accent_bold',
        'accent_unicode',
        'learning_context',
        'is_admin',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function apiUsageLogs(): HasMany
    {
        return $this->hasMany(ApiUsageLog::class);
    }

    public function csvDrafts(): HasMany
    {
        return $this->hasMany(CsvDraft::class);
    }

    public function creditPurchases(): HasMany
    {
        return $this->hasMany(CreditPurchase::class)->orderByDesc('created_at');
    }

    public function hasPersonalApiKey(): bool
    {
        return ! empty($this->openai_api_key);
    }

    /**
     * True when purchased credits should be used for AI operations.
     * Credits take priority over the personal API key whenever the balance is positive.
     */
    public function usesPlatformCredits(): bool
    {
        return $this->credits > 0;
    }

    public function canUseAi(): bool
    {
        return $this->hasPersonalApiKey() || $this->credits > 0;
    }

    public function avatarUrl(): string
    {
        return cache()->remember(
            'avatar_url_'.$this->id,
            now()->addDay(),
            fn () => 'https://api.dicebear.com/9.x/identicon/svg?seed='.rawurlencode($this->email ?: $this->name)
        );
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'openai_api_key' => 'encrypted',
            'credits' => 'integer',
            'accent_bold' => 'boolean',
            'accent_unicode' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }
}
