<?php

namespace App\Services\Integrations\Bitbucket;

class BitbucketMapper
{
    public static function repository(array $project): array
    {
        return [
            'external_id' => $project['uuid'],
            'name' => $project['name'],
            'owner' => $project['owner']['username'] ?? $project['workspace']['slug'] ?? $project['owner']['display_name'] ?? 'unknown',
            'provider' => 'bitbucket',
            'default_branch' => $project['mainbranch']['name'] ?? 'main',
        ];
    }

    public static function developer(array $user): array
    {
        return [
            'external_id' => $user['account_id'] ?? $user['uuid'] ?? null,
            'provider' => 'bitbucket',
            'username' => $user['nickname'] ?? $user['username'] ?? 'unknown',
            'name' => $user['display_name'] ?? $user['nickname'] ?? 'User',
            'email' => $user['email'] ?? null,
            'avatar' => $user['links']['avatar']['href'] ?? null,
        ];
    }

    public static function commit(array $commit): array
    {
        $date = $commit['date'] ?? null;
        return [
            'sha' => $commit['hash'],
            'message' => $commit['message'] ?? '',
            'committed_at' => $date ? \Illuminate\Support\Carbon::parse($date)->toDateTimeString() : now()->toDateTimeString(),
        ];
    }

    public static function commitAuthor(array $commit): array
    {
        $raw = $commit['author']['raw'] ?? '';
        $name = $raw;
        $email = null;

        if (preg_match('/^(.*?)\s*<(.*?)>$/', $raw, $matches)) {
            $name = trim($matches[1]);
            $email = trim($matches[2]);
        }

        return [
            'name' => $name ?: 'unknown',
            'email' => $email,
            'username' => $commit['author']['user']['nickname'] ?? $commit['author']['user']['username'] ?? $name ?: 'unknown',
            'external_id' => $commit['author']['user']['account_id'] ?? $commit['author']['user']['uuid'] ?? null,
        ];
    }

    public static function prAuthor(array $pr): array
    {
        return $pr['author'] ?? [];
    }

    public static function mergeRequest(array $pr): array
    {
        $state = strtoupper($pr['state'] ?? '');
        $status = match ($state) {
            'OPEN' => 'open',
            'MERGED' => 'merged',
            default => 'closed', // DECLINED, SUPERSEDED
        };

        return [
            'external_id' => $pr['id'],
            'title' => $pr['title'] ?? '',
            'status' => $status,
            'opened_at' => \Illuminate\Support\Carbon::parse($pr['created_on'])->toDateTimeString(),
            'merged_at' => $status === 'merged' && !empty($pr['updated_on']) ? \Illuminate\Support\Carbon::parse($pr['updated_on'])->toDateTimeString() : null,
        ];
    }

    public static function reviews(array $activities): array
    {
        $reviews = [];
        foreach ($activities as $activity) {
            if (!empty($activity['approval'])) {
                $user = $activity['approval']['user'];
                $reviews[] = [
                    'reviewer' => [
                        'external_id' => $user['account_id'] ?? $user['uuid'] ?? null,
                        'name' => $user['display_name'] ?? $user['nickname'] ?? 'unknown',
                        'username' => $user['nickname'] ?? $user['username'] ?? 'unknown',
                        'avatar' => $user['links']['avatar']['href'] ?? null,
                    ],
                    'state' => 'approved',
                    'reviewed_at' => isset($activity['approval']['date']) ? \Illuminate\Support\Carbon::parse($activity['approval']['date'])->toDateTimeString() : now()->toDateTimeString(),
                ];
            }
        }
        return $reviews;
    }

    public static function issue(array $issue): array
    {
        $state = strtolower($issue['state'] ?? '');
        $status = match ($state) {
            'new', 'open', 'on hold' => 'open',
            default => 'closed', // resolved, invalid, duplicate, wontfix, closed
        };

        return [
            'external_id' => $issue['id'],
            'title' => $issue['title'] ?? '',
            'status' => $status,
            'closed_at' => $status === 'closed' && !empty($issue['updated_on']) ? \Illuminate\Support\Carbon::parse($issue['updated_on'])->toDateTimeString() : null,
        ];
    }

    public static function deployment(array $deployment): array
    {
        $date = $deployment['created_on'] ?? null;
        return [
            'environment' => $deployment['environment']['name'] ?? 'production',
            'deployed_at' => $date ? \Illuminate\Support\Carbon::parse($date)->toDateTimeString() : now()->toDateTimeString(),
        ];
    }

    public static function deploymentAuthor(array $deployment): array
    {
        return $deployment['creator'] ?? [];
    }
}
