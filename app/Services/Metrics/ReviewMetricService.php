<?php

namespace App\Services\Metrics;

use App\Models\Developer;
use Carbon\Carbon;

class ReviewMetricService
{
    public function calculate(
        Developer $developer,
        Carbon $start,
        Carbon $end
    ): array {

        $reviews = $developer
            ->reviews()
            ->whereBetween(
                'reviewed_at',
                [$start, $end]
            );

        return [

            'reviews_done' =>
                $reviews->count(),

            'approvals' =>
                $reviews
                    ->clone()
                    ->where(
                        'state',
                        'approved'
                    )
                    ->count(),

            'changes_requested' =>
                $reviews
                    ->clone()
                    ->where(
                        'state',
                        'changes_requested'
                    )
                    ->count(),
        ];
    }
}