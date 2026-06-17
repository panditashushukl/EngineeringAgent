<?php

namespace App\Services\Metrics;

use App\Models\Developer;
use Carbon\Carbon;

class CommitMetricService
{
    public function calculate(
        Developer $developer,
        Carbon $start,
        Carbon $end
    ): array {

        $commits = $developer
            ->commits()
            ->whereBetween(
                'committed_at',
                [$start, $end]
            );

        return [

            'total_commits' =>
                $commits->count(),

            'active_days' =>
                $commits
                    ->selectRaw(
                        'DATE(committed_at) as day'
                    )
                    ->distinct()
                    ->count(),

            'average_per_day' =>
                round(
                    $commits->count()
                    /
                    max(
                        1,
                        $start->diffInDays($end)
                    ),
                    2
                ),
        ];
    }
}