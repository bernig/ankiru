<?php

namespace Database\Factories;

use App\Models\ApiUsageLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApiUsageLog>
 */
class ApiUsageLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'operation' => $this->faker->randomElement(['translation', 'stress_correction', 'tts']),
            'prompt_tokens' => $this->faker->numberBetween(50, 500),
            'completion_tokens' => $this->faker->numberBetween(20, 200),
            'characters' => null,
        ];
    }
}
