<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Integrations\ProviderFactory;
use App\Services\Integrations\MapperFactory;
use App\Jobs\SyncCommitsJob;
use App\Jobs\SyncPullRequestsJob;
use App\Jobs\SyncTasksJob;
use App\Jobs\SyncDeploymentsJob;

class SyncRepositoriesJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Integration $integration
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $provider =
            ProviderFactory::make($this->integration);

        $repositories =
            $provider->getRepositories();

        $mapper = MapperFactory::make($this->integration->provider);

        foreach ($repositories as $project) {
            try {
                $data = $mapper::repository($project);

                $repository = Repository::updateOrCreate(
                    [
                        'external_id' => (string) $data['external_id'],
                        'provider' => $this->integration->provider
                    ],
                    [
                        ...$data,
                        'integration_id' => $this->integration->id,
                    ]
                );

                if (!app()->runningUnitTests()) {
                    SyncCommitsJob::dispatch($repository);
                    SyncPullRequestsJob::dispatch($repository);
                    SyncTasksJob::dispatch($repository);
                    SyncDeploymentsJob::dispatch($repository);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error(
                    "Failed to sync repository " . ($project['name'] ?? 'unknown') . ": " . $e->getMessage(),
                    ['exception' => $e]
                );
            }
        }
    }
}

