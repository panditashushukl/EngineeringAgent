<?php

namespace App\Jobs;

use App\Models\Task;
use App\Models\Developer;
use App\Models\Repository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Integrations\ProviderFactory;
use App\Services\Integrations\MapperFactory;
use App\Jobs\CalculateMetricsJob;

class SyncTasksJob implements ShouldQueue
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

        $issues =
            $provider->getTasks(
                $this->repository->owner,
                $this->repository->name
            );

        $mapper = MapperFactory::make($this->repository->provider);
        $developerIds = [];

        foreach ($issues as $issue) {

            $developerId = null;

            if (
                !empty($issue['assignee'])
            ) {
                $developerData = $mapper::developer($issue['assignee']);

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

                $this->repository
                    ->developers()
                    ->syncWithoutDetaching([
                        $developerId
                    ]);
            }

            $mappedIssue = $mapper::issue($issue);

            Task::updateOrCreate(
                [
                    'external_id' =>
                        (string) $mappedIssue['external_id']
                ],
                [
                    'repository_id' =>
                        $this->repository->id,

                    'developer_id' =>
                        $developerId,

                    ...$mappedIssue
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

