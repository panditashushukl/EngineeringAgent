<?php

namespace App\Services\Integrations\Bitbucket;

use App\Models\Integration;
use App\Services\Integrations\Contracts\SourceControlProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class BitbucketService implements SourceControlProvider
{
    protected string $baseUrl = 'https://api.bitbucket.org/2.0';

    public function __construct(
        protected Integration $integration
    ) {
    }

    protected function client()
    {
        return Http::withToken($this->integration->access_token)
            ->acceptJson()
            ->timeout(30);
    }

    public function getAccessToken(string $code): array
    {
        return app(BitbucketOAuthService::class)->exchangeCode($code);
    }

    public function getAuthenticatedUser(): array
    {
        $user = $this->client()
            ->get("{$this->baseUrl}/user")
            ->throw()
            ->json();

        $emailsResponse = $this->client()->get("{$this->baseUrl}/user/emails");
        if ($emailsResponse->successful()) {
            $emails = $emailsResponse->json('values', []);
            $confirmedEmail = null;
            $primaryConfirmedEmail = null;

            foreach ($emails as $emailObj) {
                if (!empty($emailObj['is_confirmed'])) {
                    if (!empty($emailObj['is_primary'])) {
                        $primaryConfirmedEmail = $emailObj['email'];
                    } elseif (!$confirmedEmail) {
                        $confirmedEmail = $emailObj['email'];
                    }
                }
            }

            $user['email'] = $primaryConfirmedEmail ?? $confirmedEmail ?? null;
        } else {
            $user['email'] = null;
        }

        return $user;
    }

    public function getRepositories(): Collection
    {
        return collect(
            $this->client()
                ->get("{$this->baseUrl}/repositories", [
                    'role' => 'member',
                    'pagelen' => 100,
                ])
                ->throw()
                ->json('values', [])
        );
    }

    public function getRepository(string $owner, string $repository): array
    {
        return $this->client()
            ->get("{$this->baseUrl}/repositories/{$owner}/{$repository}")
            ->throw()
            ->json();
    }

    public function getCommits(string $owner, string $repository, array $params = []): Collection
    {
        return collect(
            $this->client()
                ->get("{$this->baseUrl}/repositories/{$owner}/{$repository}/commits", $params)
                ->throw()
                ->json('values', [])
        );
    }

    public function getPullRequests(string $owner, string $repository, array $params = []): Collection
    {
        return collect(
            $this->client()
                ->get("{$this->baseUrl}/repositories/{$owner}/{$repository}/pullrequests", $params)
                ->throw()
                ->json('values', [])
        );
    }

    public function getPullRequestReviews(
        string $owner,
        string $repository,
        string $pullRequestId
    ): Collection {
        return collect(
            $this->client()
                ->get("{$this->baseUrl}/repositories/{$owner}/{$repository}/pullrequests/{$pullRequestId}/activity")
                ->throw()
                ->json('values', [])
        );
    }

    public function getTasks(string $owner, string $repository, array $params = []): Collection
    {
        return collect(
            $this->client()
                ->get("{$this->baseUrl}/repositories/{$owner}/{$repository}/issues", $params)
                ->throw()
                ->json('values', [])
        );
    }

    public function getDeployments(string $owner, string $repository, array $params = []): Collection
    {
        try {
            return collect(
                $this->client()
                    ->get("{$this->baseUrl}/repositories/{$owner}/{$repository}/deployments", $params)
                    ->throw()
                    ->json('values', [])
            );
        } catch (\Throwable) {
            return collect();
        }
    }

    public function getContributors(string $owner, string $repository): Collection
    {
        return collect();
    }

    public function getBranches(string $owner, string $repository): Collection
    {
        return collect(
            $this->client()
                ->get("{$this->baseUrl}/repositories/{$owner}/{$repository}/refs/branches")
                ->throw()
                ->json('values', [])
        );
    }

    public function validateConnection(): bool
    {
        try {
            $this->getAuthenticatedUser();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
