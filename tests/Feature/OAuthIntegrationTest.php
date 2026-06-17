<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Integration;
use App\Services\Integrations\Github\GithubOAuthService;
use App\Services\Integrations\Gitlab\GitlabOAuthService;
use App\Services\Integrations\Bitbucket\BitbucketOAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OAuthIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_access_connect_route_and_redirects_to_github(): void
    {
        $this->mock(GithubOAuthService::class, function ($mock) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->once()
                ->andReturn('https://github.com/login/oauth/authorize?mocked=true');
        });

        $response = $this->get('/oauth/github/connect');

        $response->assertRedirect('https://github.com/login/oauth/authorize?mocked=true');
    }

    public function test_guest_callback_logs_in_existing_user(): void
    {
        $user = User::factory()->create([
            'email' => 'guest@example.com',
        ]);

        $this->mock(GithubOAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->with('test-github-code')
                ->andReturn([
                    'access_token' => 'github-access-token-123',
                    'refresh_token' => 'github-refresh-token-456',
                ]);
        });

        $githubServiceMock = $this->mock(\App\Services\Integrations\Github\GithubService::class, function ($mock) {
            $mock->shouldReceive('getAuthenticatedUser')
                ->once()
                ->andReturn([
                    'email' => 'guest@example.com',
                    'name' => 'GitHub Guest User',
                ]);
        });

        $this->app->bind(\App\Services\Integrations\Github\GithubService::class, function ($app, $params) use ($githubServiceMock) {
            return $githubServiceMock;
        });

        $response = $this->get('/oauth/github/callback?code=test-github-code');

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('integrations', [
            'user_id' => $user->id,
            'provider' => 'github',
            'access_token' => 'github-access-token-123',
            'refresh_token' => 'github-refresh-token-456',
        ]);
    }

    public function test_guest_callback_registers_new_user(): void
    {
        $this->mock(GithubOAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->with('test-github-code')
                ->andReturn([
                    'access_token' => 'github-access-token-123',
                    'refresh_token' => 'github-refresh-token-456',
                ]);
        });

        $githubServiceMock = $this->mock(\App\Services\Integrations\Github\GithubService::class, function ($mock) {
            $mock->shouldReceive('getAuthenticatedUser')
                ->once()
                ->andReturn([
                    'email' => 'new-guest@example.com',
                    'name' => 'GitHub New Guest',
                ]);
        });

        $this->app->bind(\App\Services\Integrations\Github\GithubService::class, function ($app, $params) use ($githubServiceMock) {
            return $githubServiceMock;
        });

        $response = $this->get('/oauth/github/callback?code=test-github-code');

        $response->assertRedirect('/');

        $user = User::where('email', 'new-guest@example.com')->first();
        $this->assertNotNull($user);
        $this->assertAuthenticatedAs($user);

        $this->assertDatabaseHas('integrations', [
            'user_id' => $user->id,
            'provider' => 'github',
            'access_token' => 'github-access-token-123',
            'refresh_token' => 'github-refresh-token-456',
        ]);
    }

    public function test_authenticated_user_can_connect_to_github(): void
    {
        $user = User::factory()->create();

        $this->mock(GithubOAuthService::class, function ($mock) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->once()
                ->andReturn('https://github.com/login/oauth/authorize?mocked=true');
        });

        $response = $this->actingAs($user)->get('/oauth/github/connect');

        $response->assertRedirect('https://github.com/login/oauth/authorize?mocked=true');
    }

    public function test_authenticated_user_can_connect_to_gitlab(): void
    {
        $user = User::factory()->create();

        $this->mock(GitlabOAuthService::class, function ($mock) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->once()
                ->andReturn('https://gitlab.com/oauth/authorize?mocked=true');
        });

        $response = $this->actingAs($user)->get('/oauth/gitlab/connect');

        $response->assertRedirect('https://gitlab.com/oauth/authorize?mocked=true');
    }

    public function test_authenticated_user_can_connect_to_bitbucket(): void
    {
        $user = User::factory()->create();

        $this->mock(BitbucketOAuthService::class, function ($mock) {
            $mock->shouldReceive('getAuthorizationUrl')
                ->once()
                ->andReturn('https://bitbucket.org/site/oauth2/authorize?mocked=true');
        });

        $response = $this->actingAs($user)->get('/oauth/bitbucket/connect');

        $response->assertRedirect('https://bitbucket.org/site/oauth2/authorize?mocked=true');
    }

    public function test_github_callback_saves_tokens_and_redirects(): void
    {
        $user = User::factory()->create();

        $this->mock(GithubOAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->with('test-github-code')
                ->andReturn([
                    'access_token' => 'github-access-token-123',
                    'refresh_token' => 'github-refresh-token-456',
                ]);
        });

        $response = $this->actingAs($user)->get('/oauth/github/callback?code=test-github-code');

        $response->assertRedirect('/integrations?provider=github&status=connected');

        $this->assertDatabaseHas('integrations', [
            'user_id' => $user->id,
            'provider' => 'github',
            'access_token' => 'github-access-token-123',
            'refresh_token' => 'github-refresh-token-456',
        ]);
    }

    public function test_gitlab_callback_saves_tokens_and_redirects(): void
    {
        $user = User::factory()->create();

        $this->mock(GitlabOAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->with('test-gitlab-code')
                ->andReturn([
                    'access_token' => 'gitlab-access-token-123',
                    'refresh_token' => 'gitlab-refresh-token-456',
                ]);
        });

        $response = $this->actingAs($user)->get('/oauth/gitlab/callback?code=test-gitlab-code');

        $response->assertRedirect('/integrations?provider=gitlab&status=connected');

        $this->assertDatabaseHas('integrations', [
            'user_id' => $user->id,
            'provider' => 'gitlab',
            'access_token' => 'gitlab-access-token-123',
            'refresh_token' => 'gitlab-refresh-token-456',
        ]);
    }

    public function test_bitbucket_callback_saves_tokens_and_redirects(): void
    {
        $user = User::factory()->create();

        $this->mock(BitbucketOAuthService::class, function ($mock) {
            $mock->shouldReceive('exchangeCode')
                ->once()
                ->with('test-bitbucket-code')
                ->andReturn([
                    'access_token' => 'bitbucket-access-token-123',
                    'refresh_token' => 'bitbucket-refresh-token-456',
                ]);
        });

        $response = $this->actingAs($user)->get('/oauth/bitbucket/callback?code=test-bitbucket-code');

        $response->assertRedirect('/integrations?provider=bitbucket&status=connected');

        $this->assertDatabaseHas('integrations', [
            'user_id' => $user->id,
            'provider' => 'bitbucket',
            'access_token' => 'bitbucket-access-token-123',
            'refresh_token' => 'bitbucket-refresh-token-456',
        ]);
    }

    public function test_invalid_provider_returns_validation_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/oauth/invalidprovider/connect');

        $response->assertSessionHasErrors(['provider']);
    }
}
