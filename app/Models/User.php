<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'accent_color',
        'accent_bold',
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

    public function csvDraft(): HasOne
    {
        return $this->hasOne(CsvDraft::class);
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
            'accent_bold' => 'boolean',
        ];
    }
}
