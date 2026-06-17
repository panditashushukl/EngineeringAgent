<?php

namespace App\Http\Controllers;

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
use Carbon\Carbon;
use Illuminate\View\View;
use Throwable;

class AppController extends Controller
{
    public function __invoke(): View
    {
        return view('app', [
            'appData' => $this->appData(),
        ]);
    }

    private function appData(): array
    {
        try {
            $repositories = Repository::query()
                ->withCount(['commits', 'pullRequests', 'tasks', 'deployments'])
                ->latest()
                ->limit(20)
                ->get();

            $developers = Developer::query()
                ->withCount(['commits', 'pullRequests', 'reviews', 'tasks'])
                ->with(['metrics' => fn ($query) => $query->latest()->limit(1)])
                ->latest()
                ->limit(20)
                ->get();

            $leaderboard = DeveloperMetric::query()
                ->with(['developer' => fn ($query) => $query->withCount(['commits', 'pullRequests', 'reviews'])])
                ->orderByDesc('developer_score')
                ->limit(20)
                ->get();

            $recentCommits = Commit::query()
                ->latest('committed_at')
                ->limit(80)
                ->get(['committed_at']);

            $activity = collect(range(6, 0))
                ->mapWithKeys(fn ($daysAgo) => [
                    now()->subDays($daysAgo)->format('M d') => 0,
                ]);

            foreach ($recentCommits as $commit) {
                $label = $commit->committed_at
                    ? Carbon::parse($commit->committed_at)->format('M d')
                    : null;

                if ($label && $activity->has($label)) {
                    $activity->put($label, $activity->get($label) + 1);
                }
            }

            $insights = DeveloperInsight::query()
                ->with('developer')
                ->latest()
                ->limit(8)
                ->get();

            return [
                'user' => [
                    'name' => auth()->user()?->name,
                    'email' => auth()->user()?->email,
                ],
                'overview' => [
                    'repositories' => Repository::count(),
                    'developers' => Developer::count(),
                    'commits' => Commit::count(),
                    'pull_requests' => PullRequest::count(),
                    'average_score' => round((float) DeveloperMetric::avg('developer_score'), 2),
                    'reviews' => Review::count(),
                    'tasks' => Task::count(),
                    'deployments' => Deployment::count(),
                ],
                'repositories' => $repositories,
                'developers' => $developers,
                'leaderboard' => $leaderboard,
                'integrations' => Integration::query()->where('user_id', auth()->id())->latest()->limit(12)->get(),
                'insights' => $insights,
                'activity' => [
                    'labels' => $activity->keys()->values(),
                    'commits' => $activity->values(),
                ],
            ];
        } catch (Throwable) {
            return [
                'overview' => [
                    'repositories' => 0,
                    'developers' => 0,
                    'commits' => 0,
                    'pull_requests' => 0,
                    'average_score' => 0,
                    'reviews' => 0,
                    'tasks' => 0,
                    'deployments' => 0,
                ],
                'repositories' => [],
                'developers' => [],
                'leaderboard' => [],
                'integrations' => [],
                'insights' => [],
                'activity' => [
                    'labels' => [],
                    'commits' => [],
                ],
            ];
        }
    }
}
