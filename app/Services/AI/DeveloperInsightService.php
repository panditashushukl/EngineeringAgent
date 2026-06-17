<?php

namespace App\Services\AI;

use App\Models\Developer;
use App\Models\DeveloperInsight;
use App\Models\DeveloperMetric;

class DeveloperInsightService
{
    public function __construct(
        protected PromptBuilder $promptBuilder,
        protected LLMService $llm
    ) {}

    public function generate(
        Developer $developer
    ): DeveloperInsight {

        $metric = DeveloperMetric::where(
            'developer_id',
            $developer->id
        )
        ->latest()
        ->first();

        if (!$metric) {
            $computedMetrics = app(\App\Services\Metrics\DeveloperMetricService::class)->calculate($developer);
            $metric = DeveloperMetric::create(array_merge([
                'developer_id' => $developer->id,
                'period_start' => now()->subDays(30)->toDateString(),
                'period_end' => now()->toDateString(),
            ], $computedMetrics));
        }

        $prompt =
            $this->promptBuilder
                ->developerInsight(
                    $metric
                );

        $response =
            $this->llm->ask(
                $prompt
            );

        $decoded =
            json_decode(
                $response,
                true
            );

        if (!$decoded) {
            if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (!$decoded) {

            $decoded = [

                'summary' =>
                    $response,

                'strengths' => [],
                'weaknesses' => [],
                'risks' => [],
                'recommendations' => [],
            ];
        }

        $insight = DeveloperInsight::updateOrCreate(
            [
                'developer_id' => $developer->id,
            ],
            [
                'summary' =>
                    $decoded['summary'] ?? null,

                'strengths' =>
                    $decoded['strengths'] ?? [],

                'weaknesses' =>
                    $decoded['weaknesses'] ?? [],

                'risks' =>
                    $decoded['risks'] ?? [],

                'recommendations' =>
                    $decoded['recommendations'] ?? [],

                'raw_response' =>
                    $response,
            ]
        );

        // Keep only the latest insight for this developer
        DeveloperInsight::where('developer_id', $developer->id)
            ->where('id', '!=', $insight->id)
            ->delete();

        return $insight;
    }
}