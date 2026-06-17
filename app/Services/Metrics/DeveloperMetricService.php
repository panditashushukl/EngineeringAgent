<?php

namespace App\Services\Metrics;

use App\Models\Developer;
use Carbon\Carbon;

class DeveloperMetricService
{
    public function __construct(
        protected CommitMetricService $commitService,
        protected ReviewMetricService $reviewService,
        protected TaskMetricService $taskService,
        protected DeliveryMetricService $deliveryService,
        protected DeveloperScoreService $scoreService
    ) {}

    public function calculate(
        Developer $developer,
        ?Carbon $start = null,
        ?Carbon $end = null
    ): array {

        $start ??= now()->subDays(30);
        $end ??= now();

        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        $commitMetrics =
            $this->commitService
                ->calculate(
                    $developer,
                    $start,
                    $end
                );

        $reviewMetrics =
            $this->reviewService
                ->calculate(
                    $developer,
                    $start,
                    $end
                );

        $taskMetrics =
            $this->taskService
                ->calculate(
                    $developer,
                    $start,
                    $end
                );

        $deliveryMetrics =
            $this->deliveryService
                ->calculate(
                    $developer,
                    $start,
                    $end
                );

        $taskCompletionScore =
            $taskMetrics['completion_rate'];

        $reviewScore =
            min(
                100,
                $reviewMetrics['reviews_done'] * 5
            );

        $deliveryScore =
            $deliveryMetrics['delivery_score'];

        $codeQualityScore =
            min(
                100,
                $commitMetrics['active_days'] * 4
            );

        $developerScore =
            $this->scoreService
                ->calculate([

                    'task_completion_score' =>
                        $taskCompletionScore,

                    'review_score' =>
                        $reviewScore,

                    'delivery_speed_score' =>
                        $deliveryScore,

                    'code_quality_score' =>
                        $codeQualityScore,
                ]);

        return [

            'commits' =>
                $commitMetrics['total_commits'],

            'prs_created' =>
                $developer
                    ->pullRequests()
                    ->count(),

            'prs_merged' =>
                $developer
                    ->pullRequests()
                    ->whereNotNull(
                        'merged_at'
                    )
                    ->count(),

            'reviews_done' =>
                $reviewMetrics['reviews_done'],

            'bugs_fixed' =>
                $developer
                    ->bugFixes()
                    ->count(),

            'deployments' =>
                $deliveryMetrics['deployments'],

            'task_completion_score' =>
                $taskCompletionScore,

            'code_quality_score' =>
                $codeQualityScore,

            'review_score' =>
                $reviewScore,

            'delivery_speed_score' =>
                $deliveryScore,

            'developer_score' =>
                $developerScore,
        ];
    }
}