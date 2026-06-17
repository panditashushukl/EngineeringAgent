<?php

namespace Database\Factories;

use App\Models\Developer;
use App\Models\PullRequest;
use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Review>
 */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pull_request_id' => PullRequest::factory(),
            'reviewer_id' => Developer::factory(),
            'state' => fake()->randomElement(['approved', 'changes_requested', 'commented']),
            'reviewed_at' => fake()->dateTimeThisYear(),
        ];
    }
}
