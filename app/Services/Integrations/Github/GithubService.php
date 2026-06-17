<?php

namespace App\Services\Integrations\Github;

use App\Models\Integration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use App\Services\Integrations\Contracts\SourceControlProvider;

class GithubService implements SourceControlProvider
{
    protected Integration $integration;

    protected string $baseUrl =
        'https://api.github.com';

    public function __construct(
        Integration $integration
    ) {
        $this->integration = $integration;
    }

    protected function client()
    {
        return Http::withToken(
            $this->integration->access_token
        )
        ->withHeaders([
            'User-Agent' => 'Engineering-Agent',
        ])
        ->acceptJson();
    }

    public function getRepositories(): Collection
    {
        $response = $this->client()->get("{$this->baseUrl}/user/repos", [
            'per_page' => 100,
            'sort' => 'updated',
        ]);

        return collect($response->successful() ? $response->json() : []);
    }

    public function getRepository(
        string $owner,
        string $repository
    ): array {
        $response = $this->client()->get("{$this->baseUrl}/repos/{$owner}/{$repository}");
        return $response->successful() ? $response->json() : [];
    }

    public function getCommits(
        string $owner,
        string $repository,
        array $params = []
    ): Collection {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repository}/commits",
            array_merge(['per_page' => 100], $params)
        );
        return collect($response->successful() ? $response->json() : []);
    }

    public function getPullRequests(
        string $owner,
        string $repository,
        array $params = []
    ): Collection {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repository}/pulls",
            array_merge(['state' => 'all', 'per_page' => 100], $params)
        );
        return collect($response->successful() ? $response->json() : []);
    }

    public function getPullRequestReviews(
        string $owner,
        string $repository,
        string $pullRequestId
    ): Collection {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repository}/pulls/{$pullRequestId}/reviews"
        );
        return collect($response->successful() ? $response->json() : []);
    }

    public function getTasks(
        string $owner,
        string $repository,
        array $params = []
    ): Collection {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repository}/issues",
            array_merge(['state' => 'all', 'per_page' => 100], $params)
        );

        if ($response->failed()) {
            return collect();
        }

        return collect($response->json())->reject(fn($issue) => isset($issue['pull_request']));
    }

    public function getDeployments(
        string $owner,
        string $repository,
        array $params = []
    ): Collection {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repository}/deployments",
            array_merge(['per_page' => 100], $params)
        );
        return collect($response->successful() ? $response->json() : []);
    }

    public function getContributors(
        string $owner,
        string $repository
    ): Collection {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repository}/contributors",
            ['per_page' => 100]
        );
        return collect($response->successful() ? $response->json() : []);
    }

    public function getBranches(
        string $owner,
        string $repository
    ): Collection {
        $response = $this->client()->get(
            "{$this->baseUrl}/repos/{$owner}/{$repository}/branches",
            ['per_page' => 100]
        );
        return collect($response->successful() ? $response->json() : []);
    }

    public function getAuthenticatedUser(): array
    {
        $response = $this->client()->get("{$this->baseUrl}/user");

        if ($response->failed()) {
            return [];
        }

        $user = $response->json();

        $emailsResponse = $this->client()->get("{$this->baseUrl}/user/emails");
        if ($emailsResponse->successful()) {
            $emails = $emailsResponse->json();
            $verifiedEmail = null;
            $primaryVerifiedEmail = null;

            foreach ($emails as $emailObj) {
                if (!empty($emailObj['verified'])) {
                    if (!empty($emailObj['primary'])) {
                        $primaryVerifiedEmail = $emailObj['email'];
                    } elseif (!$verifiedEmail) {
                        $verifiedEmail = $emailObj['email'];
                    }
                }
            }

            $user['email'] = $primaryVerifiedEmail ?? $verifiedEmail ?? null;
        } elseif (empty($user['email'])) {
            $user['email'] = null;
        }

        return $user;
    }

    public function getAccessToken(
        string $code
    ): array {
        return app(GithubOAuthService::class)->exchangeCode($code);
    }

    public function validateConnection(): bool
    {
        return true;
    }
}
