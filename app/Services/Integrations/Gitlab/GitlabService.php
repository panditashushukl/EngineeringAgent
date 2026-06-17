<?php

namespace App\Services\Integrations\Gitlab;

use App\Models\Integration;
use App\Services\Integrations\Contracts\SourceControlProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class GitlabService implements SourceControlProvider
{
    protected string $baseUrl;

    public function __construct(
        protected Integration $integration
    ) {
        $this->baseUrl = config(
            'services.gitlab.base_url',
            'https://gitlab.com/api/v4'
        );
    }

    protected function client()
    {
        return Http::withToken($this->integration->access_token)
            ->acceptJson()
            ->timeout(30);
    }

    protected function get(string $endpoint, array $params = []): array
    {
        return $this->client()
            ->get($this->baseUrl . $endpoint, $params)
            ->throw()
            ->json();
    }

    public function getAccessToken(string $code): array
    {
        return app(GitlabOAuthService::class)->exchangeCode($code);
    }

    public function getAuthenticatedUser(): array
    {
        return $this->get('/user');
    }

    public function getRepositories(): Collection
    {
        return collect($this->get('/projects', [
            'membership' => true,
            'per_page' => 100,
            'order_by' => 'last_activity_at',
            'sort' => 'desc',
        ]));
    }

    public function getRepository(string $owner, string $repository): array
    {
        $projectId = $repository ? urlencode("{$owner}/{$repository}") : $owner;
        return $this->get("/projects/{$projectId}");
    }

    public function getCommits(string $owner, string $repository = '', array $params = []): Collection
    {
        $projectId = $repository ? urlencode("{$owner}/{$repository}") : $owner;
        return collect($this->get("/projects/{$projectId}/repository/commits", $params));
    }

    public function getPullRequests(string $owner, string $repository = '', array $params = []): Collection
    {
        $projectId = $repository ? urlencode("{$owner}/{$repository}") : $owner;
        return collect($this->get("/projects/{$projectId}/merge_requests", $params));
    }

    public function getPullRequestReviews(
        string $owner,
        string $repository,
        string $pullRequestId
    ): Collection {
        $projectId = $repository ? urlencode("{$owner}/{$repository}") : $owner;
        return collect($this->get("/projects/{$projectId}/merge_requests/{$pullRequestId}/approvals"));
    }

    public function getTasks(string $owner, string $repository = '', array $params = []): Collection
    {
        $projectId = $repository ? urlencode("{$owner}/{$repository}") : $owner;
        return collect($this->get("/projects/{$projectId}/issues", $params));
    }

    public function getDeployments(string $owner, string $repository = '', array $params = []): Collection
    {
        $projectId = $repository ? urlencode("{$owner}/{$repository}") : $owner;
        return collect($this->get("/projects/{$projectId}/deployments", $params));
    }

    public function getContributors(string $owner, string $repository): Collection
    {
        $projectId = $repository ? urlencode("{$owner}/{$repository}") : $owner;
        return collect($this->get("/projects/{$projectId}/repository/contributors"));
    }

    public function getBranches(string $owner, string $repository): Collection
    {
        $projectId = $repository ? urlencode("{$owner}/{$repository}") : $owner;
        return collect($this->get("/projects/{$projectId}/repository/branches"));
    }

    public function validateConnection(): bool
    {
        try {
            $user = $this->getAuthenticatedUser();
            return !empty($user['id']);
        } catch (\Throwable) {
            return false;
        }
    }
}
