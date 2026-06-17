<?php

namespace App\Http\Controllers;

use App\Models\Developer;
use App\Models\DeveloperMetric;
use App\Models\Repository;
use App\Jobs\CalculateMetricsJob;
use App\Services\Queue\QueueHelper;

class DeveloperController extends Controller
{
    public function index()
    {
        return Developer::paginate();
    }

    public function show(
        Developer $developer
    ) {
        return $developer->load([
            'repositories',
            'commits',
            'pullRequests'
        ]);
    }

    public function metrics(
        Developer $developer
    ) {
        return DeveloperMetric::where(
            'developer_id',
            $developer->id
        )
        ->latest()
        ->first();
    }

    public function calculate(
        Developer $developer
    ) {
        CalculateMetricsJob::dispatch(
            $developer
        );

        QueueHelper::runWorkerInBackground();

        return response()->json([
            'success' => true,
            'message' => 'Metric calculation queued'
        ]);
    }

    public function calculateAll()
    {
        Developer::query()
            ->each(fn (Developer $developer) => CalculateMetricsJob::dispatch($developer));

        QueueHelper::runWorkerInBackground();

        return response()->json([
            'success' => true,
            'message' => 'Metric calculation queued for all developers'
        ]);
    }

    public function repositories(Developer $developer)
    {
        return Repository::query()
            ->whereHas('commits', fn ($query) => $query->where('developer_id', $developer->id))
            ->orWhereHas('pullRequests', fn ($query) => $query->where('developer_id', $developer->id))
            ->orWhereHas('tasks', fn ($query) => $query->where('developer_id', $developer->id))
            ->orWhereHas('deployments', fn ($query) => $query->where('developer_id', $developer->id))
            ->withCount([
                'commits',
                'pullRequests',
                'tasks',
                'deployments',
            ])
            ->paginate();
    }

    public function commits(Developer $developer)
    {
        return $developer->commits()->latest('committed_at')->paginate();
    }

    public function pullRequests(Developer $developer)
    {
        return $developer->pullRequests()->latest('opened_at')->paginate();
    }

    public function reviews(Developer $developer)
    {
        return $developer->reviews()->latest('reviewed_at')->paginate();
    }

    public function tasks(Developer $developer)
    {
        return $developer->tasks()->latest()->paginate();
    }

    public function deployments(Developer $developer)
    {
        return $developer->deployments()->latest('deployed_at')->paginate();
    }
}
