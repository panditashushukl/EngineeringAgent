<?php

namespace App\Jobs;

use App\Models\Review;
use App\Models\Developer;
use App\Models\PullRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Integrations\ProviderFactory;
use App\Services\Integrations\MapperFactory;
use App\Jobs\CalculateMetricsJob;

class SyncReviewsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected PullRequest $pullRequest
    ){}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $repository =
            $this->pullRequest->repository;

        $provider =
            ProviderFactory::make(
                $repository->integration
            );

        $rawReviews =
            $provider->getPullRequestReviews(
                $repository->owner,
                $repository->name,
                $this->pullRequest->external_id
            );

        $mapper = MapperFactory::make($repository->provider);

        $mappedReviews = $mapper::reviews(
            $rawReviews instanceof \Illuminate\Support\Collection ? $rawReviews->toArray() : (array) $rawReviews
        );

        $developerIds = [];

        foreach ($mappedReviews as $mappedReview) {
            $reviewerData = $mappedReview['reviewer'];

            $reviewer = Developer::findOrCreateForProvider(
                $repository->provider,
                (string) $reviewerData['external_id'],
                $reviewerData['username'],
                null,
                [
                    'name' => $reviewerData['name'] ?? $reviewerData['username'],
                    'avatar' => $reviewerData['avatar'] ?? null,
                ]
            );

            $developerIds[] = $reviewer->id;

            Review::updateOrCreate(
                [
                    'pull_request_id' =>
                        $this->pullRequest->id,

                    'reviewer_id' =>
                        $reviewer->id
                ],
                [
                    'state' =>
                        $mappedReview['state'] ?? 'approved',

                    'reviewed_at' =>
                        $mappedReview['reviewed_at'] ?? now()
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

