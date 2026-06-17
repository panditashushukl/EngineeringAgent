<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Jobs\SyncCommitsJob;
use App\Jobs\SyncPullRequestsJob;
use App\Jobs\SyncReviewsJob;
use App\Jobs\SyncTasksJob;
use App\Jobs\SyncDeploymentsJob;
use App\Services\Queue\QueueHelper;

class RepositoryController extends Controller
{
    public function index()
    {
        return Repository::withCount([
            'commits',
            'pullRequests'
        ])->paginate();
    }

    public function show(
        Repository $repository
    ) {
        return $repository->load([
            'commits',
            'pullRequests',
            'tasks',
            'deployments'
        ]);
    }

    public function sync(
        Repository $repository
    ) {

        SyncCommitsJob::dispatch(
            $repository
        );

        SyncPullRequestsJob::dispatch(
            $repository
        );

        SyncTasksJob::dispatch(
            $repository
        );

        SyncDeploymentsJob::dispatch(
            $repository
        );

        QueueHelper::runWorkerInBackground();

        return response()->json([
            'success' => true,
            'message' => 'Repository sync queued'
        ]);
    }

    public function syncCommits(Repository $repository)
    {
        SyncCommitsJob::dispatch($repository);

        QueueHelper::runWorkerInBackground();

        return response()->json([
            'success' => true,
            'message' => 'Commit sync queued'
        ]);
    }

    public function syncPullRequests(Repository $repository)
    {
        SyncPullRequestsJob::dispatch($repository);

        QueueHelper::runWorkerInBackground();

        return response()->json([
            'success' => true,
            'message' => 'Pull request sync queued'
        ]);
    }

    public function syncReviews(Repository $repository)
    {
        $repository->pullRequests()->each(fn ($pr) => SyncReviewsJob::dispatch($pr));

        QueueHelper::runWorkerInBackground();

        return response()->json([
            'success' => true,
            'message' => 'Review sync queued'
        ]);
    }

    public function syncTasks(Repository $repository)
    {
        SyncTasksJob::dispatch($repository);

        QueueHelper::runWorkerInBackground();

        return response()->json([
            'success' => true,
            'message' => 'Task sync queued'
        ]);
    }

    public function syncDeployments(Repository $repository)
    {
        SyncDeploymentsJob::dispatch($repository);

        QueueHelper::runWorkerInBackground();

        return response()->json([
            'success' => true,
            'message' => 'Deployment sync queued'
        ]);
    }
}
