<?php

namespace Database\Seeders;

use App\Models\BugFix;
use App\Models\Commit;
use App\Models\Deployment;
use App\Models\Developer;
use App\Models\DeveloperInsight;
use App\Models\DeveloperMetric;
use App\Models\Integration;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\Review;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Services\AI\DeveloperInsightService;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(DeveloperInsightService $insightService): void
    {
        // 1. Create Test User
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Create Demo User for seeded integrations so they are not pre-connected on the test user
        $demoUser = User::factory()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.local',
        ]);

        // 2. Create Integrations for demo user
        $integrations = collect(['github', 'gitlab'])->map(function ($provider) use ($demoUser) {
            return Integration::factory()->create([
                'user_id' => $demoUser->id,
                'provider' => $provider,
            ]);
        });

        // 3. Create Repositories for each integration
        $repositories = collect();
        $integrations->each(function ($integration) use (&$repositories) {
            $repos = Repository::factory(3)->create([
                'integration_id' => $integration->id,
                'provider' => $integration->provider,
            ]);
            $repositories = $repositories->merge($repos);
        });

        // 4. Create Developers
        $developers = Developer::factory(8)->create();

        // 5. Connect Developers to Repositories (pivot table)
        // Each repository gets a random subset of 3-5 developers
        $repositories->each(function ($repository) use ($developers) {
            $repoDevs = $developers->random(rand(3, 5));
            $repository->developers()->attach($repoDevs->pluck('id'));
        });

        // 6. Seed Repository Data (Commits, PRs, Tasks, Deployments)
        $repositories->each(function ($repository) {
            // Get developers attached to this repo
            $repoDevs = $repository->developers;
            if ($repoDevs->isEmpty()) {
                return;
            }

            // Seed 15-30 Commits
            Commit::factory(rand(15, 30))->sequence(fn () => [
                'repository_id' => $repository->id,
                'developer_id' => $repoDevs->random()->id,
            ])->create();

            // Seed 5-10 Pull Requests
            $prs = PullRequest::factory(rand(5, 10))->sequence(fn () => [
                'repository_id' => $repository->id,
                'developer_id' => $repoDevs->random()->id,
            ])->create();

            // For each Pull Request:
            $prs->each(function ($pr) use ($repoDevs) {
                // Seed 1-3 Reviews from other developers in the repository
                $otherDevs = $repoDevs->reject(fn ($dev) => $dev->id === $pr->developer_id);
                if ($otherDevs->isNotEmpty()) {
                    Review::factory(rand(1, min(3, $otherDevs->count())))->sequence(fn () => [
                        'pull_request_id' => $pr->id,
                        'reviewer_id' => $otherDevs->random()->id,
                    ])->create();
                }

                // Conditional bug fix: 30% chance if PR is closed or merged
                if (rand(1, 100) <= 30) {
                    BugFix::factory()->create([
                        'pull_request_id' => $pr->id,
                        'developer_id' => $pr->developer_id,
                    ]);
                }
            });

            // Seed 3-6 Deployments
            Deployment::factory(rand(3, 6))->sequence(fn () => [
                'repository_id' => $repository->id,
                'developer_id' => $repoDevs->random()->id,
            ])->create();

            // Seed 8-15 Tasks
            Task::factory(rand(8, 15))->sequence(fn () => [
                'repository_id' => $repository->id,
                'developer_id' => $repoDevs->random()->id,
            ])->create();
        });

        // 7. Seed Developer Metrics & Insights
        $developers->each(function ($developer) use ($insightService) {
            // Seed weekly metrics for the last 4 weeks
            for ($i = 0; $i < 4; $i++) {
                $start = now()->subWeeks($i + 1)->startOfWeek();
                $end = (clone $start)->endOfWeek();

                DeveloperMetric::factory()->create([
                    'developer_id' => $developer->id,
                    'period_start' => $start->format('Y-m-d'),
                    'period_end' => $end->format('Y-m-d'),
                ]);
            }

            // Seed a DeveloperInsight record using active AI provider with factory fallback
            try {
                $insightService->generate($developer);
            } catch (\Exception $e) {
                $provider = \App\Models\Setting::get('ai_provider', config('services.ai_provider', 'gemini'));
                $providerName = ucfirst($provider);
                $this->command->warn("Failed to generate AI insights via {$providerName} for developer {$developer->name}: " . $e->getMessage());
                DeveloperInsight::factory()->create([
                    'developer_id' => $developer->id,
                ]);
            }
        });
    }
}
