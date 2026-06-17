<?php

namespace Database\Factories;

use App\Models\Developer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Developer>
 */
class DeveloperFactory extends Factory
{
    protected $model = Developer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'external_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'provider' => fake()->randomElement(['github', 'gitlab', 'bitbucket']),
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'username' => fake()->unique()->userName(),
            'avatar' => fake()->imageUrl(100, 100, 'people'),
        ];
    }
}
