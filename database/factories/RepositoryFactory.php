<?php

namespace Database\Factories;

use App\Models\Integration;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Repository>
 */
class RepositoryFactory extends Factory
{
    protected $model = Repository::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'integration_id' => Integration::factory(),
            'external_id' => (string) fake()->unique()->numberBetween(100000, 999999),
            'name' => fake()->unique()->slug(2),
            'owner' => fake()->userName(),
            'provider' => fake()->randomElement(['github', 'gitlab', 'bitbucket']),
            'default_branch' => fake()->randomElement(['main', 'master', 'develop']),
        ];
    }
}
