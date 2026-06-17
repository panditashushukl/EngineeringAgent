<?php

namespace Database\Factories;

use App\Models\BugFix;
use App\Models\Developer;
use App\Models\PullRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BugFix>
 */
class BugFixFactory extends Factory
{
    protected $model = BugFix::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pull_request_id' => PullRequest::factory(),
            'developer_id' => Developer::factory(),
            'reason' => fake()->sentence(),
        ];
    }
}
