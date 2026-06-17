<?php

namespace Database\Factories;

use App\Models\Developer;
use App\Models\DeveloperInsight;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeveloperInsight>
 */
class DeveloperInsightFactory extends Factory
{
    protected $model = DeveloperInsight::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'developer_id' => Developer::factory(),
            'summary' => fake()->paragraph(),
            'strengths' => [
                fake()->sentence(),
                fake()->sentence(),
            ],
            'weaknesses' => [
                fake()->sentence(),
            ],
            'risks' => [
                fake()->sentence(),
            ],
            'recommendations' => [
                fake()->sentence(),
                fake()->sentence(),
            ],
            'raw_response' => fake()->text(),
        ];
    }
}
