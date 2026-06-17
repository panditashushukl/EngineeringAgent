<?php

namespace App\Jobs;

use App\Models\Repository;
use App\Models\Deployment;
use App\Models\Developer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Integrations\ProviderFactory;
use App\Services\Integrations\MapperFactory;
use App\Jobs\CalculateMetricsJob;

class SyncDeploymentsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Repository $repository
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $provider =
            ProviderFactory::make(
                $this->repository->integration
            );

        $deployments =
            $provider->getDeployments(
                $this->repository->owner,
                $this->repository->name
            );

        $mapper = MapperFactory::make($this->repository->provider);
        $developerIds = [];

        foreach ($deployments as $deployment) {

            $developerId = null;

            $authorRaw = $mapper::deploymentAuthor($deployment);

            if (!empty($authorRaw)) {
                $developerData = $mapper::developer($authorRaw);

                $developer = Developer::findOrCreateForProvider(
                    $this->repository->provider,
                    (string) $developerData['external_id'],
                    $developerData['username'],
                    $developerData['email'] ?? null,
                    [
                        'name' => $developerData['name'] ?? null,
                        'avatar' => $developerData['avatar'] ?? null,
                    ]
                );

                $developerId =
                    $developer->id;

                $developerIds[] = $developerId;
            }

            $mappedDeployment = $mapper::deployment($deployment);

            Deployment::updateOrCreate(
                [
                    'repository_id' =>
                        $this->repository->id,

                    'environment' =>
                        $mappedDeployment['environment'],

                    'deployed_at' =>
                        $mappedDeployment['deployed_at']
                ],
                [
                    'developer_id' =>
                        $developerId
                ]
            );
        }

        if (!app()->runningUnitTests()) {
            foreach (array_unique(array_filter($developerIds)) as $id) {
                $dev = Developer::find($id);
                if ($dev) {
                    CalculateMetricsJob::dispatch($dev);
                }
            }
        }
    }
}

