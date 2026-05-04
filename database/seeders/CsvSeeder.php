<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CsvSeeder extends Seeder
{
    /**
     * Sample French to Russian translation pairs.
     * Russian text uses <b>vowel</b> tags to mark lexical stress.
     * Only words with two or more vowels carry a stress tag.
     *
     * @var array<int, array{0: string, 1: string}>
     */
    private const SAMPLE_ROWS = [
        ['Je travaille depuis chez moi.', 'Я раб<b>о</b>таю из д<b>о</b>ма.'],
        ['Je suis developpeur web.', 'Я веб-разраб<b>о</b>тчик.'],
        ['Bonjour, comment allez-vous ?', 'Здр<b>а</b>вствуйте, как вы пожив<b>а</b>ете?'],
        ['Il fait beau aujourd\'hui.', 'Сег<b>о</b>дня хор<b>о</b>шая пог<b>о</b>да.'],
        ['Je parle un peu le russe.', 'Я немн<b>о</b>го говор<b>ю</b> по-р<b>у</b>сски.'],
        ['Merci beaucoup.', 'Больш<b>о</b>е сп<b>а</b>сибо.'],
        ['Je t\'aime.', 'Я люб<b>л</b>ю теб<b>я</b>.'],
        ['Ou habitez-vous ?', 'Где вы жив<b>ё</b>те?'],
        ['Mon chien s\'appelle Max.', 'Мо<b>ю</b> соб<b>а</b>ку зов<b>у</b>т Макс.'],
        ['Je lis beaucoup de livres.', 'Я мн<b>о</b>го чит<b>а</b>ю книг.'],
        ['Le cafe est chaud.', 'К<b>о</b>фе гор<b>я</b>чий.'],
        ['J\'apprends le russe depuis six mois.', 'Я уч<b>у</b> р<b>у</b>сский яз<b>ы</b>к уж<b>е</b> шесть мес<b>я</b>цев.'],
        ['Le train arrive dans cinq minutes.', 'П<b>о</b>езд прибыв<b>а</b>ет ч<b>е</b>рез пять мин<b>у</b>т.'],
        ['Je voudrais un verre d\'eau, s\'il vous plait.', 'Дайте мне, пожалуйста, ст<b>а</b>кан в<b>о</b>ды.'],
        ['Mon professeur est tres patient.', 'Мой преподав<b>а</b>тель оч<b>е</b>нь терпел<b>и</b>в.'],
    ];

    /**
     * Seed the CSV editor temp file with sample translation data.
     * This allows developers to open the app with data already loaded
     * without having to upload a real CSV file.
     */
    public function run(): void
    {
        $data = [
            'csvRows' => self::SAMPLE_ROWS,
            'originalFileName' => 'sample_translations.csv',
            'hasCsvLoaded' => true,
            'savedAt' => now()->toIso8601String(),
        ];
        file_put_contents(
            storage_path('app/csv_editor_temp.json'),
            json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
        );
        $this->command->info('CSV editor seeded with '.count(self::SAMPLE_ROWS).' sample translation rows.');
    }
}
