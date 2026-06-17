<?php

namespace Database\Factories;

use App\Models\Commit;
use App\Models\Developer;
use App\Models\Repository;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Commit>
 */
class CommitFactory extends Factory
{
    protected $model = Commit::class;

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
            'sha' => fake()->unique()->sha256(),
            'message' => fake()->sentence(),
            'committed_at' => fake()->dateTimeThisYear(),
        ];
    }
}
