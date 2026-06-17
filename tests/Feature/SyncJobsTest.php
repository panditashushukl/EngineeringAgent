<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Integration;
use App\Models\Repository;
use App\Models\Developer;
use App\Models\Commit;
use App\Models\PullRequest;
use App\Models\Review;
use App\Models\Task;
use App\Models\Deployment;
use App\Jobs\SyncRepositoriesJob;
use App\Jobs\SyncCommitsJob;
use App\Jobs\SyncPullRequestsJob;
use App\Jobs\SyncReviewsJob;
use App\Jobs\SyncTasksJob;
use App\Jobs\SyncDeploymentsJob;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SyncJobsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_repositories_job_github(): void
    {
        $user = User::factory()->create();
        $integration = Integration::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'access_token' => 'token-123',
        ]);

        Http::fake([
            'api.github.com/user/repos*' => Http::response([
                [
                    'id' => 12345,
                    'name' => 'test-repo',
                    'owner' => ['login' => 'test-owner'],
                    'default_branch' => 'main',
                ]
            ], 200)
        ]);

        (new SyncRepositoriesJob($integration))->handle();

        $this->assertDatabaseHas('repositories', [
            'external_id' => '12345',
            'name' => 'test-repo',
            'owner' => 'test-owner',
            'provider' => 'github',
            'integration_id' => $integration->id,
        ]);
    }

    public function test_sync_commits_job_github(): void
    {
        $user = User::factory()->create();
        $integration = Integration::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'access_token' => 'token-123',
        ]);
        $repository = Repository::create([
            'integration_id' => $integration->id,
            'external_id' => '12345',
            'provider' => 'github',
            'owner' => 'test-owner',
            'name' => 'test-repo',
        ]);

        Http::fake([
            'api.github.com/repos/test-owner/test-repo/commits*' => Http::response([
                [
                    'sha' => 'commit-sha-123',
                    'commit' => [
                        'message' => 'Fix a major bug',
                        'author' => [
                            'name' => 'John Doe',
                            'email' => 'john@example.com',
                            'date' => '2026-06-10T12:00:00Z',
                        ]
                    ],
                    'author' => [
                        'id' => 111,
                        'login' => 'johndoe',
                    ]
                ]
            ], 200)
        ]);

        (new SyncCommitsJob($repository))->handle();

        $this->assertDatabaseHas('developers', [
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'provider' => 'github',
        ]);

        $this->assertDatabaseHas('commits', [
            'sha' => 'commit-sha-123',
            'message' => 'Fix a major bug',
            'repository_id' => $repository->id,
        ]);
    }

    public function test_sync_pull_requests_job_bitbucket(): void
    {
        $user = User::factory()->create();
        $integration = Integration::create([
            'user_id' => $user->id,
            'provider' => 'bitbucket',
            'access_token' => 'token-123',
        ]);
        $repository = Repository::create([
            'integration_id' => $integration->id,
            'external_id' => '98765',
            'provider' => 'bitbucket',
            'owner' => 'test-workspace',
            'name' => 'test-repo',
        ]);

        Http::fake([
            'api.bitbucket.org/2.0/repositories/test-workspace/test-repo/pullrequests*' => Http::response([
                'values' => [
                    [
                        'id' => 99,
                        'title' => 'Feature pull request',
                        'state' => 'MERGED',
                        'created_on' => '2026-06-10T12:00:00Z',
                        'updated_on' => '2026-06-10T13:00:00Z',
                        'author' => [
                            'account_id' => 'bitbucket-author-uuid',
                            'nickname' => 'bb-dev',
                            'display_name' => 'BB Developer',
                            'links' => [
                                'avatar' => ['href' => 'https://example.com/avatar.png']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        (new SyncPullRequestsJob($repository))->handle();

        $this->assertDatabaseHas('developers', [
            'external_id' => 'bitbucket-author-uuid',
            'username' => 'bb-dev',
            'provider' => 'bitbucket',
        ]);

        $this->assertDatabaseHas('pull_requests', [
            'external_id' => '99',
            'status' => 'merged',
            'repository_id' => $repository->id,
        ]);
    }

    public function test_sync_reviews_job_github(): void
    {
        $user = User::factory()->create();
        $integration = Integration::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'access_token' => 'token-123',
        ]);
        $repository = Repository::create([
            'integration_id' => $integration->id,
            'external_id' => '12345',
            'provider' => 'github',
            'owner' => 'test-owner',
            'name' => 'test-repo',
        ]);
        $developer = Developer::create([
            'external_id' => '54321',
            'username' => 'johndoe',
            'name' => 'John Doe',
            'provider' => 'github',
        ]);
        $pullRequest = PullRequest::create([
            'repository_id' => $repository->id,
            'developer_id' => $developer->id,
            'external_id' => '12',
            'title' => 'My PR',
            'status' => 'open',
            'opened_at' => now(),
        ]);

        Http::fake([
            'api.github.com/repos/test-owner/test-repo/pulls/12/reviews*' => Http::response([
                [
                    'state' => 'APPROVED',
                    'submitted_at' => '2026-06-10T14:00:00Z',
                    'user' => [
                        'id' => 54321,
                        'login' => 'reviewer-login',
                        'avatar_url' => 'https://example.com/reviewer.png',
                    ]
                ]
            ], 200)
        ]);

        (new SyncReviewsJob($pullRequest))->handle();

        $this->assertDatabaseHas('developers', [
            'external_id' => '54321',
            'username' => 'reviewer-login',
            'provider' => 'github',
        ]);

        $this->assertDatabaseHas('reviews', [
            'pull_request_id' => $pullRequest->id,
            'state' => 'approved',
        ]);
    }

    public function test_sync_tasks_job_gitlab(): void
    {
        $user = User::factory()->create();
        $integration = Integration::create([
            'user_id' => $user->id,
            'provider' => 'gitlab',
            'access_token' => 'token-123',
        ]);
        $repository = Repository::create([
            'integration_id' => $integration->id,
            'external_id' => '11111',
            'provider' => 'gitlab',
            'owner' => 'test-group',
            'name' => 'test-project',
        ]);

        Http::fake([
            'gitlab.com/api/v4/projects/test-group%2Ftest-project/issues*' => Http::response([
                [
                    'id' => 77,
                    'title' => 'Fix issue #77',
                    'state' => 'opened',
                    'closed_at' => null,
                    'assignee' => [
                        'id' => 888,
                        'username' => 'gitlab-dev',
                        'name' => 'Gitlab Developer',
                    ]
                ]
            ], 200)
        ]);

        (new SyncTasksJob($repository))->handle();

        $this->assertDatabaseHas('developers', [
            'external_id' => '888',
            'username' => 'gitlab-dev',
            'provider' => 'gitlab',
        ]);

        $this->assertDatabaseHas('tasks', [
            'external_id' => '77',
            'status' => 'open',
            'repository_id' => $repository->id,
        ]);
    }

    public function test_sync_deployments_job_github(): void
    {
        $user = User::factory()->create();
        $integration = Integration::create([
            'user_id' => $user->id,
            'provider' => 'github',
            'access_token' => 'token-123',
        ]);
        $repository = Repository::create([
            'integration_id' => $integration->id,
            'external_id' => '12345',
            'provider' => 'github',
            'owner' => 'test-owner',
            'name' => 'test-repo',
        ]);

        Http::fake([
            'api.github.com/repos/test-owner/test-repo/deployments*' => Http::response([
                [
                    'id' => 55,
                    'environment' => 'staging',
                    'created_at' => '2026-06-10T15:00:00Z',
                    'creator' => [
                        'id' => 999,
                        'login' => 'deployer',
                    ]
                ]
            ], 200)
        ]);

        (new SyncDeploymentsJob($repository))->handle();

        $this->assertDatabaseHas('developers', [
            'external_id' => '999',
            'username' => 'deployer',
            'provider' => 'github',
        ]);

        $this->assertDatabaseHas('deployments', [
            'environment' => 'staging',
            'deployed_at' => '2026-06-10T15:00:00Z',
            'repository_id' => $repository->id,
        ]);
    }
}
