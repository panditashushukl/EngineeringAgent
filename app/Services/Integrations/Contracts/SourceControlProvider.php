<?php

namespace App\Services\Integrations\Contracts;

use App\Models\Repository;
use Illuminate\Support\Collection;

interface SourceControlProvider
{
    /**
     * Exchange OAuth code for access token.
     */
    public function getAccessToken(string $code): array;

    /**
     * Return authenticated user information.
     */
    public function getAuthenticatedUser(): array;

    /**
     * Get repositories.
     */
    public function getRepositories(): Collection;

    /**
     * Get repository details.
     */
    public function getRepository(
        string $owner,
        string $repository
    ): array;

    /**
     * Get commits.
     */
    public function getCommits(
        string $owner,
        string $repository,
        array $params = []
    ): Collection;

    /**
     * Get pull requests.
     */
    public function getPullRequests(
        string $owner,
        string $repository,
        array $params = []
    ): Collection;

    /**
     * Get reviews for pull request.
     */
    public function getPullRequestReviews(
        string $owner,
        string $repository,
        string $pullRequestId
    ): Collection;

    /**
     * Get issues/tasks.
     */
    public function getTasks(
        string $owner,
        string $repository,
        array $params = []
    ): Collection;

    /**
     * Get deployments.
     */
    public function getDeployments(
        string $owner,
        string $repository,
        array $params = []
    ): Collection;

    /**
     * Get repository contributors.
     */
    public function getContributors(
        string $owner,
        string $repository
    ): Collection;

    /**
     * Get branches.
     */
    public function getBranches(
        string $owner,
        string $repository
    ): Collection;

    /**
     * Verify credentials.
     */
    public function validateConnection(): bool;
}