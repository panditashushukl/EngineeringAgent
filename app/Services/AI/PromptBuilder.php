<?php

namespace App\Services\AI;

use App\Models\DeveloperMetric;

class PromptBuilder
{
    public function developerInsight(
        DeveloperMetric $metric
    ): string {
        $developerName = $metric->developer?->name ?? 'Developer';

        return <<<PROMPT

You are a Senior Engineering Manager.

Analyze the following metrics for developer {$developerName} with a focus on commit behavior, code quality, and repository contribution.

Developer Score:
{$metric->developer_score}

Commits:
{$metric->commits}

PRs Created:
{$metric->prs_created}

PRs Merged:
{$metric->prs_merged}

Reviews Done:
{$metric->reviews_done}

Deployments:
{$metric->deployments}

Task Completion Score:
{$metric->task_completion_score}

Code Quality Score:
{$metric->code_quality_score}

Review Score:
{$metric->review_score}

Delivery Speed Score:
{$metric->delivery_speed_score}

Use these metrics to infer if the developer is producing high-quality code, whether commits are consistent and well-reviewed, and whether there are clear opportunities to improve engineering performance.

Generate JSON response:

{
  "summary":"",
  "strengths":[],
  "weaknesses":[],
  "risks":[],
  "recommendations":[]
}

Do not return markdown.

PROMPT;
    }
}