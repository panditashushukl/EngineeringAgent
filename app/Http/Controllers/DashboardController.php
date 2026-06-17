<?php

namespace App\Http\Controllers;

use App\Models\Repository;
use App\Models\Developer;
use App\Models\Commit;
use App\Models\PullRequest;
use App\Models\DeveloperMetric;
use App\Models\Review;
use App\Models\Task;
use App\Models\Deployment;

class DashboardController extends Controller
{
    public function overview()
    {
        return [

            'repositories' =>
                Repository::count(),

            'developers' =>
                Developer::count(),

            'commits' =>
                Commit::count(),

            'pull_requests' =>
                PullRequest::count(),

            'average_score' =>
                round(
                    DeveloperMetric::avg(
                        'developer_score'
                    ),
                    2
                )
        ];
    }

    public function leaderboard()
    {
        return DeveloperMetric::query()
            ->with(['developer' => fn ($query) => $query->withCount(['commits', 'pullRequests', 'reviews'])])
            ->orderByDesc(
                'developer_score'
            )
            ->paginate(20);
    }

    public function repositories()
    {
        return Repository::query()
            ->withCount([
                'commits',
                'pullRequests',
                'tasks',
                'deployments',
            ])
            ->latest()
            ->paginate(20);
    }

    public function developers()
    {
        return Developer::query()
            ->withCount([
                'commits',
                'pullRequests',
                'reviews',
                'tasks',
                'deployments',
            ])
            ->latest()
            ->paginate(20);
    }

    public function metrics()
    {
        return [
            'reviews' => Review::count(),
            'tasks' => Task::count(),
            'deployments' => Deployment::count(),
            'pull_requests' => PullRequest::count(),
            'average_score' => round(
                (float) DeveloperMetric::avg('developer_score'),
                2
            ),
        ];
    }
}
