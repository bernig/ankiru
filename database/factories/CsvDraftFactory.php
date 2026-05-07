<?php

namespace Database\Factories;

use App\Models\CsvDraft;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CsvDraft>
 */
class CsvDraftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'original_file_name' => 'translations.csv',
            'csv_rows' => [
                ['Bonjour.', 'Привет.'],
            ],
            'has_csv_loaded' => true,
        ];
    }
}
