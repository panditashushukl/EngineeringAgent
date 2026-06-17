<?php

namespace App\Jobs;

use App\Models\Developer;
use App\Models\DeveloperMetric;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\Metrics\DeveloperMetricService;
use App\Jobs\GenerateDeveloperInsightsJob;

class CalculateMetricsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected ?Developer $developer = null
    ) {}

    public function handle(
        DeveloperMetricService $metricService
    ): void {

        $developers = $this->developer
            ? collect([$this->developer])
            : Developer::cursor();

        foreach ($developers as $developer) {

            $metrics =
                $metricService->calculate(
                    $developer
                );

            DeveloperMetric::updateOrCreate(
                [
                    'developer_id' => $developer->id,
                    'period_start' => now()->subDays(30)->toDateString(),
                    'period_end' => now()->toDateString(),
                ],
                $metrics
            );

            if (!app()->runningUnitTests()) {
                GenerateDeveloperInsightsJob::dispatch($developer);
            }
        }
    }
}