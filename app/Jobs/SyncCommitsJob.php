<?php

namespace App\Jobs;

use App\Models\Commit;
use App\Models\Developer;
use App\Models\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Integrations\ProviderFactory;
use App\Services\Integrations\MapperFactory;
use App\Jobs\CalculateMetricsJob;

class SyncCommitsJob implements ShouldQueue
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

        $commits =
            $provider->getCommits(
                $this->repository->owner,
                $this->repository->name
            );

        $mapper = MapperFactory::make($this->repository->provider);
        $developerIds = [];

        foreach ($commits as $commit) {

            $authorData = $mapper::commitAuthor($commit);

            $author = Developer::findOrCreateForProvider(
                $this->repository->provider,
                $authorData['external_id'] ?? null,
                $authorData['username'],
                $authorData['email'] ?? null,
                [
                    'name' => $authorData['name'] ?? null,
                ]
            );

            $developerIds[] = $author->id;

            $this->repository
                ->developers()
                ->syncWithoutDetaching([
                    $author->id
                ]);

            $mappedCommit = $mapper::commit($commit);

            Commit::updateOrCreate(
                [
                    'sha' =>
                        $mappedCommit['sha']
                ],
                [
                    'repository_id' =>
                        $this->repository->id,

                    'developer_id' =>
                        $author->id,

                    ...$mappedCommit
                ]
            );
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

