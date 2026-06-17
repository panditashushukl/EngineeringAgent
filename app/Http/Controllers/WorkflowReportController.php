<?php

namespace App\Http\Controllers;

use App\Models\BugFix;
use App\Models\Commit;
use App\Models\Deployment;
use App\Models\Developer;
use App\Models\PullRequest;
use App\Models\Repository;
use App\Models\Review;
use App\Models\Task;
use App\Models\WorkflowReport;
use App\Services\AI\LLMService;
use Illuminate\Http\Request;

class WorkflowReportController extends Controller
{
    public function latest()
    {
        $report = WorkflowReport::latest()->first();

        return response()->json([
            'success' => true,
            'report' => $report
        ]);
    }

    public function generate(Request $request, LLMService $llm)
    {
        // 1. Gather snapshot metrics
        $metrics = [
            'repositories' => Repository::count(),
            'developers' => Developer::count(),
            'commits' => Commit::count(),
            'pull_requests' => PullRequest::count(),
            'reviews' => Review::count(),
            'tasks' => Task::count(),
            'deployments' => Deployment::count(),
            'bugs_fixed' => BugFix::count(),
        ];

        // 2. Build AI prompt
        $prompt = "You are the Engineering Agent, an advanced AI development cockpit assistant. Analyze the following engineering workspace metrics:\n";
        $prompt .= "- Repositories: {$metrics['repositories']}\n";
        $prompt .= "- Developers: {$metrics['developers']}\n";
        $prompt .= "- Commits: {$metrics['commits']}\n";
        $prompt .= "- Pull Requests: {$metrics['pull_requests']}\n";
        $prompt .= "- Code Reviews: {$metrics['reviews']}\n";
        $prompt .= "- Tasks: {$metrics['tasks']}\n";
        $prompt .= "- Deployments: {$metrics['deployments']}\n";
        $prompt .= "- Bug Fixes: {$metrics['bugs_fixed']}\n\n";
        $prompt .= "Based on these stats, generate a professional, highly detailed, data-driven AI Workflow & Velocity Report on the current development state of the workspace.\n";
        $prompt .= "You MUST return a JSON object with the following structure:\n";
        $prompt .= "{\n";
        $prompt .= "  \"executive_summary\": \"A detailed high-level summary of the engineering health (at least 2-3 sentences)...\",\n";
        $prompt .= "  \"velocity\": \"A detailed analysis of commits, PRs, and task execution rates (at least 2-3 sentences)...\",\n";
        $prompt .= "  \"collaboration\": \"A detailed analysis of review participation culture and bug fix density (at least 2-3 sentences)...\",\n";
        $prompt .= "  \"delivery\": \"A detailed analysis of release cadence and deployment agility (at least 2-3 sentences)...\",\n";
        $prompt .= "  \"recommendations\": [\n";
        $prompt .= "    \"Recommendation 1...\",\n";
        $prompt .= "    \"Recommendation 2...\",\n";
        $prompt .= "    \"Recommendation 3...\"\n";
        $prompt .= "  ]\n";
        $prompt .= "}\n\n";
        $prompt .= "Do not return any markdown or extra text outside the JSON. Return only valid JSON.";

        // Build fallback
        $fallback = [
            'executive_summary' => "This report evaluates the engineering workspace health across {$metrics['developers']} developers and {$metrics['repositories']} repositories. The overall health is stable, with active contributions in commits and deployments.",
            'velocity' => "A total of {$metrics['commits']} commits and {$metrics['pull_requests']} pull requests have been indexed. Developer throughput is steady, showing regular code contributions and active progress.",
            'collaboration' => "{$metrics['reviews']} code reviews have been completed. Peer reviews are actively practiced, helping maintain high code quality standards. Bug resolution includes {$metrics['bugs_fixed']} verified bug fixes.",
            'delivery' => "{$metrics['deployments']} deployments have been successfully released. The cadence of shipping code is frequent, indicating an agile development cycle.",
            'recommendations' => [
                "Enhance Review Culture: Ensure all key pull requests receive at least two peer approvals to reduce regressions.",
                "Automate Deployments: Standardize pipeline triggers to maintain regular ship cycles and reduce manual overhead.",
                "Task Alignment: Align pending repository issues with developer skillsets to optimize backlog burn-down rates."
            ]
        ];

        try {
            // 3. Query LLM via LLMService
            $response = $llm->ask($prompt);
            
            // Clean up JSON quotes/code block formatting if Ollama wraps it
            $cleanText = trim($response);
            if (str_starts_with($cleanText, '```json')) {
                $cleanText = substr($cleanText, 7);
            }
            if (str_starts_with($cleanText, '```')) {
                $cleanText = substr($cleanText, 3);
            }
            if (str_ends_with($cleanText, '```')) {
                $cleanText = substr($cleanText, 0, -3);
            }
            $cleanText = trim($cleanText);

            $decoded = json_decode($cleanText, true);
            if (!$decoded) {
                // Try regex search for {...}
                if (preg_match('/\{[\s\S]*\}/', $cleanText, $matches)) {
                    $decoded = json_decode($matches[0], true);
                }
            }

            if ($decoded && isset($decoded['executive_summary'])) {
                // Ensure recommendations is an array
                if (isset($decoded['recommendations']) && is_string($decoded['recommendations'])) {
                    $decoded['recommendations'] = array_filter(array_map('trim', explode("\n", $decoded['recommendations'])));
                }

                $report = WorkflowReport::create([
                    'report_text' => json_encode($decoded),
                    'metrics_snapshot' => $metrics
                ]);

                return response()->json([
                    'success' => true,
                    'report' => $report
                ]);
            }
            
            throw new \Exception("Invalid JSON structure received from Ollama.");

        } catch (\Exception $e) {
            $report = WorkflowReport::create([
                'report_text' => json_encode($fallback),
                'metrics_snapshot' => $metrics
            ]);

            return response()->json([
                'success' => true,
                'report' => $report,
                'warning' => 'Ollama query timed out or is offline. A local heuristics-based analysis report has been generated instead.'
            ]);
        }
    }
}
