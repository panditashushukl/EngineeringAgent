<?php

namespace App\Services\Integrations\Github;

class GithubMapper
{
    public static function repository(array $project): array
    {
        return [
            'external_id' => $project['id'],
            'name' => $project['name'],
            'owner' => $project['owner']['login'] ?? 'unknown',
            'provider' => 'github',
            'default_branch' => $project['default_branch'] ?? 'main',
        ];
    }

    public static function developer(array $user): array
    {
        return [
            'external_id' => $user['id'],
            'provider' => 'github',
            'username' => $user['login'],
            'name' => $user['name'] ?? $user['login'],
            'email' => $user['email'] ?? null,
            'avatar' => $user['avatar_url'] ?? null,
        ];
    }

    public static function commit(array $commit): array
    {
        $date = $commit['commit']['author']['date'] ?? null;
        return [
            'sha' => $commit['sha'],
            'message' => $commit['commit']['message'] ?? '',
            'committed_at' => $date ? \Illuminate\Support\Carbon::parse($date)->toDateTimeString() : now()->toDateTimeString(),
        ];
    }

    public static function commitAuthor(array $commit): array
    {
        return [
            'name' => $commit['commit']['author']['name'] ?? 'unknown',
            'email' => $commit['commit']['author']['email'] ?? null,
            'username' => $commit['author']['login'] ?? $commit['commit']['author']['name'] ?? 'unknown',
            'external_id' => $commit['author']['id'] ?? null,
        ];
    }

    public static function prAuthor(array $pr): array
    {
        return $pr['user'] ?? [];
    }

    public static function mergeRequest(array $pr): array
    {
        $status = 'open';
        if (strtolower($pr['state'] ?? '') === 'closed') {
            $status = !empty($pr['merged_at']) ? 'merged' : 'closed';
        }

        return [
            'external_id' => $pr['id'],
            'title' => $pr['title'] ?? '',
            'status' => $status,
            'opened_at' => \Illuminate\Support\Carbon::parse($pr['created_at'])->toDateTimeString(),
            'merged_at' => !empty($pr['merged_at']) ? \Illuminate\Support\Carbon::parse($pr['merged_at'])->toDateTimeString() : null,
        ];
    }

    public static function reviews(array $rawReviews): array
    {
        $reviews = [];
        foreach ($rawReviews as $review) {
            $state = match (strtolower($review['state'] ?? '')) {
                'approved' => 'approved',
                'changes_requested' => 'changes_requested',
                default => 'commented',
            };
            $reviews[] = [
                'reviewer' => [
                    'external_id' => $review['user']['id'] ?? null,
                    'name' => $review['user']['login'] ?? 'unknown',
                    'username' => $review['user']['login'] ?? 'unknown',
                    'avatar' => $review['user']['avatar_url'] ?? null,
                ],
                'state' => $state,
                'reviewed_at' => isset($review['submitted_at']) ? \Illuminate\Support\Carbon::parse($review['submitted_at'])->toDateTimeString() : now()->toDateTimeString(),
            ];
        }
        return $reviews;
    }

    public static function issue(array $issue): array
    {
        $status = match (strtolower($issue['state'] ?? '')) {
            'closed' => 'closed',
            default => 'open',
        };

        return [
            'external_id' => $issue['id'],
            'title' => $issue['title'] ?? '',
            'status' => $status,
            'closed_at' => !empty($issue['closed_at']) ? \Illuminate\Support\Carbon::parse($issue['closed_at'])->toDateTimeString() : null,
        ];
    }

    public static function deployment(array $deployment): array
    {
        $date = $deployment['created_at'] ?? null;
        return [
            'environment' => $deployment['environment'] ?? 'production',
            'deployed_at' => $date ? \Illuminate\Support\Carbon::parse($date)->toDateTimeString() : now()->toDateTimeString(),
        ];
    }

    public static function deploymentAuthor(array $deployment): array
    {
        return $deployment['creator'] ?? [];
    }
}
