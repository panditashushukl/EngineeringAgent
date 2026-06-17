<?php

namespace Database\Factories;

use App\Models\Deployment;
use App\Models\Developer;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deployment>
 */
class DeploymentFactory extends Factory
{
    protected $model = Deployment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'repository_id' => Repository::factory(),
            'developer_id' => Developer::factory(),
            'environment' => fake()->randomElement(['production', 'staging', 'development']),
            'deployed_at' => fake()->dateTimeThisYear(),
        ];
    }
}
