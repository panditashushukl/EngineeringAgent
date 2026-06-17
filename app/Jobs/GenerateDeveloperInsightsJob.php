<?php

namespace App\Jobs;

use App\Models\Developer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use App\Services\AI\DeveloperInsightService;

class GenerateDeveloperInsightsJob
    implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Developer $developer
    ) {}

    public function handle(
        DeveloperInsightService $service
    ): void {

        $service->generate(
            $this->developer
        );
    }
}