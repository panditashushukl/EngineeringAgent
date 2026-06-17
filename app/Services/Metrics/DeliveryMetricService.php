<?php

namespace App\Services\Metrics;

use App\Models\Developer;
use Carbon\Carbon;

class DeliveryMetricService
{
    public function calculate(
        Developer $developer,
        Carbon $start,
        Carbon $end
    ): array {

        $deployments = $developer
            ->deployments()
            ->whereBetween(
                'deployed_at',
                [$start, $end]
            );

        $mergedPrs = $developer
            ->pullRequests()
            ->whereNotNull(
                'merged_at'
            )
            ->whereBetween(
                'merged_at',
                [$start, $end]
            );

        return [

            'deployments' =>
                $deployments->count(),

            'merged_prs' =>
                $mergedPrs->count(),

            'delivery_score' =>
                min(
                    100,
                    (
                        $deployments->count() * 10
                    ) +
                    (
                        $mergedPrs->count() * 2
                    )
                ),
        ];
    }
}