<?php

namespace App\Services\Metrics;

use App\Models\Developer;
use Carbon\Carbon;

class TaskMetricService
{
    public function calculate(
        Developer $developer,
        Carbon $start,
        Carbon $end
    ): array {

        $tasks = $developer
            ->tasks()
            ->whereBetween(
                'created_at',
                [$start, $end]
            );

        $completed =
            $tasks
                ->clone()
                ->where(
                    'status',
                    'closed'
                )
                ->count();

        $total =
            $tasks->count();

        return [

            'tasks_assigned' =>
                $total,

            'tasks_completed' =>
                $completed,

            'completion_rate' =>
                $total
                ? round(
                    ($completed / $total) * 100,
                    2
                )
                : 0,
        ];
    }
}