<?php

namespace Database\Factories;

use App\Models\Developer;
use App\Models\DeveloperMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DeveloperMetric>
 */
class DeveloperMetricFactory extends Factory
{
    protected $model = DeveloperMetric::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeThisYear();
        $end = (clone $start)->modify('+7 days');

        $taskScore = fake()->randomFloat(2, 50, 100);
        $codeQualityScore = fake()->randomFloat(2, 50, 100);
        $reviewScore = fake()->randomFloat(2, 50, 100);
        $deliveryScore = fake()->randomFloat(2, 50, 100);

        $developerScore = round(
            ($taskScore * 0.40) +
            ($codeQualityScore * 0.20) +
            ($reviewScore * 0.20) +
            ($deliveryScore * 0.20),
            2
        );

        return [
            'developer_id' => Developer::factory(),
            'period_start' => $start->format('Y-m-d'),
            'period_end' => $end->format('Y-m-d'),
            'commits' => fake()->numberBetween(0, 100),
            'prs_created' => fake()->numberBetween(0, 20),
            'prs_merged' => fake()->numberBetween(0, 20),
            'reviews_done' => fake()->numberBetween(0, 30),
            'bugs_fixed' => fake()->numberBetween(0, 15),
            'deployments' => fake()->numberBetween(0, 10),
            'task_completion_score' => $taskScore,
            'code_quality_score' => $codeQualityScore,
            'review_score' => $reviewScore,
            'delivery_speed_score' => $deliveryScore,
            'developer_score' => $developerScore,
        ];
    }
}
