# Ankiru

Créez vos cartes Anki russe en quelques clics.

Traduisez vos phrases en russe avec accents toniques, générez les audios et exportez un deck `.apkg` prêt à importer dans Anki — le tout assisté par l'IA.

## Fonctionnalités

- **Traduction IA** — traduit automatiquement vos phrases en russe naturel, avec les accents toniques placés sur chaque mot.
- **Correction des accents** — l'IA vérifie et corrige les accents toniques de textes russes existants pour une prononciation correcte.
- **Audio synthétisé** — génère un fichier audio haute qualité pour chaque phrase russe, intégré directement dans vos cartes Anki.
- **Export Anki** — exporte un fichier `.apkg` prêt à importer dans Anki, avec l'audio embarqué dans chaque carte.

## Stack technique

- PHP 8.3 / Laravel 13
- Livewire 4 + Flux UI 2
- Tailwind CSS 4
- OpenAI (traduction, accents toniques, TTS)
- SQLite (par défaut)

## Prérequis

- PHP >= 8.3
- Composer
- Node.js >= 20
- Une clé API OpenAI

## Installation

```bash
git clone https://github.com/bernig/ankiru.git
cd ankiru

composer install
cp .env.example .env
php artisan key:generate
```

Configurer le fichier `.env` :

```env
APP_URL=http://localhost:8000
```

> **Clé OpenAI** — chaque utilisateur renseigne sa propre clé OpenAI directement dans l'application après connexion. Il n'y a pas de clé sitewide à configurer.

Créer la base de données et lancer les migrations :

```bash
touch database/database.sqlite
php artisan migrate
```

Installer les assets et lancer le serveur de développement :

```bash
npm install
composer run dev
```

## Tests

```bash
php artisan test --compact
```

## Licence

MIT
