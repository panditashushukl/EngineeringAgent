<?php

namespace Database\Factories;

use App\Models\Developer;
use App\Models\PullRequest;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PullRequest>
 */
class PullRequestFactory extends Factory
{
    protected $model = PullRequest::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['open', 'closed', 'merged']);
        $openedAt = fake()->dateTimeThisYear();
        $mergedAt = $status === 'merged' ? fake()->dateTimeBetween($openedAt, 'now') : null;

        return [
            'repository_id' => Repository::factory(),
            'developer_id' => Developer::factory(),
            'external_id' => (string) fake()->unique()->numberBetween(1, 10000),
            'title' => fake()->sentence(),
            'status' => $status,
            'opened_at' => $openedAt,
            'merged_at' => $mergedAt,
        ];
    }
}
