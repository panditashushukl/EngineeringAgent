<?php

namespace App\Services\Integrations\Gitlab;

class GitlabMapper
{
    public static function repository(array $project): array
    {
        return [
            'external_id' => $project['id'],
            'name' => $project['name'],
            'owner' => $project['namespace']['path'] ?? $project['namespace']['name'] ?? 'unknown',
            'provider' => 'gitlab',
            'default_branch' => $project['default_branch'] ?? 'main',
        ];
    }

    public static function developer(array $user): array
    {
        return [
            'external_id' => $user['id'],
            'provider' => 'gitlab',
            'username' => $user['username'],
            'name' => $user['name'] ?? $user['username'],
            'email' => $user['email'] ?? null,
            'avatar' => $user['avatar_url'] ?? null,
        ];
    }

    public static function commit(array $commit): array
    {
        return [
            'sha' => $commit['id'],
            'message' => $commit['message'],
            'committed_at' => \Illuminate\Support\Carbon::parse($commit['created_at'])->toDateTimeString(),
        ];
    }

    public static function commitAuthor(array $commit): array
    {
        return [
            'name' => $commit['author_name'] ?? 'unknown',
            'email' => $commit['author_email'] ?? null,
            'username' => $commit['author_name'] ?? 'unknown',
        ];
    }

    public static function prAuthor(array $mr): array
    {
        return $mr['author'] ?? [];
    }

    public static function mergeRequest(array $mr): array
    {
        // Map GitLab 'opened' state to our DB enum 'open'
        $status = match (strtolower($mr['state'] ?? '')) {
            'opened' => 'open',
            'closed' => 'closed',
            'merged' => 'merged',
            default => 'open',
        };

        return [
            'external_id' => $mr['id'],
            'title' => $mr['title'],
            'status' => $status,
            'opened_at' => \Illuminate\Support\Carbon::parse($mr['created_at'])->toDateTimeString(),
            'merged_at' => !empty($mr['merged_at']) ? \Illuminate\Support\Carbon::parse($mr['merged_at'])->toDateTimeString() : null,
        ];
    }

    public static function reviews(array $approvalsResponse): array
    {
        $approvedBy = $approvalsResponse['approved_by'] ?? [];
        $reviews = [];
        foreach ($approvedBy as $approval) {
            $reviews[] = [
                'reviewer' => [
                    'external_id' => $approval['user']['id'],
                    'name' => $approval['user']['name'] ?? $approval['user']['username'],
                    'username' => $approval['user']['username'],
                    'avatar' => $approval['user']['avatar_url'] ?? null,
                ],
                'state' => 'approved',
                'reviewed_at' => now()->toDateTimeString(),
            ];
        }
        return $reviews;
    }

    public static function issue(array $issue): array
    {
        $status = match (strtolower($issue['state'] ?? '')) {
            'opened', 'open' => 'open',
            default => 'closed',
        };

        return [
            'external_id' => $issue['id'],
            'title' => $issue['title'],
            'status' => $status,
            'closed_at' => !empty($issue['closed_at']) ? \Illuminate\Support\Carbon::parse($issue['closed_at'])->toDateTimeString() : null,
        ];
    }

    public static function deployment(array $deployment): array
    {
        return [
            'environment' => $deployment['environment']['name'] ?? 'production',
            'deployed_at' => \Illuminate\Support\Carbon::parse($deployment['created_at'])->toDateTimeString(),
        ];
    }

    public static function deploymentAuthor(array $deployment): array
    {
        return $deployment['user'] ?? [];
    }
}
