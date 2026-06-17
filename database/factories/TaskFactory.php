<?php

namespace Database\Factories;

use App\Models\Developer;
use App\Models\Repository;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['open', 'closed']);
        $closedAt = $status === 'closed' ? fake()->dateTimeThisYear() : null;

        return [
            'repository_id' => Repository::factory(),
            'developer_id' => Developer::factory(),
            'external_id' => (string) fake()->unique()->numberBetween(1, 10000),
            'title' => fake()->sentence(),
            'status' => $status,
            'closed_at' => $closedAt,
        ];
    }
}
