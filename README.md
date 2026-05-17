# Ankiru

Create Russian Anki flashcards in just a few clicks — free and open source.

Translate phrases into Russian with stress marks, generate audio, and export a `.apkg` deck ready to import into Anki — all AI-powered.

## Features

- **AI Translation** — automatically translates your phrases into natural Russian, with stress marks placed on every word.
- **Stress correction** — AI checks and corrects stress marks on existing Russian text for accurate pronunciation.
- **Synthesized audio** — generates a high-quality audio file for each Russian phrase, embedded directly in your Anki cards.
- **Anki export** — exports a `.apkg` file ready to import into Anki, with audio included in each card.

## Tech stack

- PHP 8.3 / Laravel 13
- Livewire 4 + Flux UI 2
- Tailwind CSS 4
- OpenAI (translation, stress marks, TTS)
- SQLite (local) / MySQL (production)

## Requirements

- PHP >= 8.3
- Composer
- Node.js >= 22

## Dependencies requiring a license

**Flux UI Pro** (`livewire/flux-pro`) is a paid UI component library. `composer install` will fail without valid credentials.

1. Purchase a license at [fluxui.dev](https://fluxui.dev).
2. Add your credentials to `~/.composer/auth.json`:

```json
{
    "http-basic": {
        "composer.fluxui.dev": {
            "username": "your@email.com",
            "password": "your-license-key"
        }
    }
}
```

Alternatively, set the environment variable before running Composer:

```bash
COMPOSER_AUTH='{"http-basic":{"composer.fluxui.dev":{"username":"your@email.com","password":"your-license-key"}}}' composer install
```

## Installation

```bash
git clone https://github.com/bernig/ankiru.git
cd ankiru

composer install
cp .env.example .env
php artisan key:generate
```

Configure your `.env`:

```env
APP_URL=http://localhost:8000
```

Create the database and run migrations:

```bash
touch database/database.sqlite
php artisan migrate
```

Install assets and start the development server:

```bash
npm install
composer run dev
```

> **OpenAI API key** — each user enters their own OpenAI API key directly in the app after signing in. There is no sitewide key to configure.

## Admin panel

A built-in admin panel is available at `/admin`. It shows registered users, CSV drafts, per-user AI usage statistics, and a live Laravel log viewer.

To grant admin access to a user:

```bash
php artisan admin:grant user@example.com
```

Only users with `is_admin = true` in the database can access `/admin`. There is no hardcoded admin account.

## Production

In production, the queue worker and Reverb WebSocket server must run continuously. Use Supervisor to manage them:

```ini
[program:ankiru-worker]
command=php /var/www/ankiru.org/artisan queue:work --sleep=3 --tries=3
directory=/var/www/ankiru.org
user=deployer
autostart=true
autorestart=true

[program:ankiru-reverb]
command=php /var/www/ankiru.org/artisan reverb:start
directory=/var/www/ankiru.org
user=deployer
autostart=true
autorestart=true
```

## Tests

```bash
php artisan test --compact
```

## License

[MIT](LICENSE) © 2026 [Bernig](https://github.com/bernig)

You are free to use, copy, modify, and distribute this project, provided you retain the copyright notice.
