<?php

namespace App\Jobs;

use App\Models\Developer;
use App\Models\Repository;
use App\Models\PullRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Integrations\ProviderFactory;
use App\Services\Integrations\MapperFactory;
use App\Jobs\SyncReviewsJob;
use App\Jobs\CalculateMetricsJob;

class SyncPullRequestsJob implements ShouldQueue
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

        $mrs =
            $provider->getPullRequests(
                $this->repository->owner,
                $this->repository->name
            );

        $mapper = MapperFactory::make($this->repository->provider);
        $developerIds = [];

        foreach ($mrs as $mr) {

            $authorRaw = $mapper::prAuthor($mr);
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

            $developerIds[] = $developer->id;

            $this->repository
                ->developers()
                ->syncWithoutDetaching([
                    $developer->id
                ]);

            $mappedPR = $mapper::mergeRequest($mr);

            $pullRequest = PullRequest::updateOrCreate(
                [
                    'external_id' =>
                        (string) $mappedPR['external_id']
                ],
                [
                    'repository_id' =>
                        $this->repository->id,

                    'developer_id' =>
                        $developer->id,

                    ...$mappedPR
                ]
            );

            if (!app()->runningUnitTests()) {
                SyncReviewsJob::dispatch($pullRequest);
            }
        }

        if (!app()->runningUnitTests()) {
            foreach (array_unique($developerIds) as $id) {
                $dev = Developer::find($id);
                if ($dev) {
                    CalculateMetricsJob::dispatch($dev);
                }
            }
        }
    }
}

