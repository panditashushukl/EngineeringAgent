<?php

namespace Tests\Feature;

use App\Models\Integration;
use App\Services\Integrations\Github\GithubService;
use App\Services\Integrations\Bitbucket\BitbucketService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OAuthServiceEmailTest extends TestCase
{
    public function test_github_returns_primary_verified_email(): void
    {
        $integration = new Integration(['access_token' => 'mock-token']);
        $service = new GithubService($integration);

        Http::fake([
            'api.github.com/user' => Http::response([
                'login' => 'testuser',
                'email' => 'public@example.com',
            ], 200),
            'api.github.com/user/emails' => Http::response([
                [
                    'email' => 'primary-verified@example.com',
                    'primary' => true,
                    'verified' => true,
                ],
                [
                    'email' => 'secondary-verified@example.com',
                    'primary' => false,
                    'verified' => true,
                ],
            ], 200),
        ]);

        $user = $service->getAuthenticatedUser();

        $this->assertEquals('primary-verified@example.com', $user['email']);
    }

    public function test_github_returns_secondary_verified_email_if_primary_is_unverified(): void
    {
        $integration = new Integration(['access_token' => 'mock-token']);
        $service = new GithubService($integration);

        Http::fake([
            'api.github.com/user' => Http::response([
                'login' => 'testuser',
                'email' => null,
            ], 200),
            'api.github.com/user/emails' => Http::response([
                [
                    'email' => 'primary-unverified@example.com',
                    'primary' => true,
                    'verified' => false,
                ],
                [
                    'email' => 'secondary-verified@example.com',
                    'primary' => false,
                    'verified' => true,
                ],
            ], 200),
        ]);

        $user = $service->getAuthenticatedUser();

        $this->assertEquals('secondary-verified@example.com', $user['email']);
    }

    public function test_github_returns_null_if_no_verified_email(): void
    {
        $integration = new Integration(['access_token' => 'mock-token']);
        $service = new GithubService($integration);

        Http::fake([
            'api.github.com/user' => Http::response([
                'login' => 'testuser',
                'email' => null,
            ], 200),
            'api.github.com/user/emails' => Http::response([
                [
                    'email' => 'primary-unverified@example.com',
                    'primary' => true,
                    'verified' => false,
                ],
            ], 200),
        ]);

        $user = $service->getAuthenticatedUser();

        $this->assertNull($user['email']);
    }

    public function test_github_falls_back_to_public_email_if_emails_api_fails(): void
    {
        $integration = new Integration(['access_token' => 'mock-token']);
        $service = new GithubService($integration);

        Http::fake([
            'api.github.com/user' => Http::response([
                'login' => 'testuser',
                'email' => 'public@example.com',
            ], 200),
            'api.github.com/user/emails' => Http::response([], 403),
        ]);

        $user = $service->getAuthenticatedUser();

        $this->assertEquals('public@example.com', $user['email']);
    }

    public function test_bitbucket_returns_primary_confirmed_email(): void
    {
        $integration = new Integration(['access_token' => 'mock-token']);
        $service = new BitbucketService($integration);

        Http::fake([
            'api.bitbucket.org/2.0/user' => Http::response([
                'display_name' => 'Test User',
            ], 200),
            'api.bitbucket.org/2.0/user/emails' => Http::response([
                'values' => [
                    [
                        'email' => 'secondary-confirmed@example.com',
                        'is_primary' => false,
                        'is_confirmed' => true,
                    ],
                    [
                        'email' => 'primary-confirmed@example.com',
                        'is_primary' => true,
                        'is_confirmed' => true,
                    ],
                ],
            ], 200),
        ]);

        $user = $service->getAuthenticatedUser();

        $this->assertEquals('primary-confirmed@example.com', $user['email']);
    }

    public function test_bitbucket_returns_secondary_confirmed_email_if_primary_is_unconfirmed(): void
    {
        $integration = new Integration(['access_token' => 'mock-token']);
        $service = new BitbucketService($integration);

        Http::fake([
            'api.bitbucket.org/2.0/user' => Http::response([
                'display_name' => 'Test User',
            ], 200),
            'api.bitbucket.org/2.0/user/emails' => Http::response([
                'values' => [
                    [
                        'email' => 'primary-unconfirmed@example.com',
                        'is_primary' => true,
                        'is_confirmed' => false,
                    ],
                    [
                        'email' => 'secondary-confirmed@example.com',
                        'is_primary' => false,
                        'is_confirmed' => true,
                    ],
                ],
            ], 200),
        ]);

        $user = $service->getAuthenticatedUser();

        $this->assertEquals('secondary-confirmed@example.com', $user['email']);
    }

    public function test_bitbucket_returns_null_if_no_confirmed_email(): void
    {
        $integration = new Integration(['access_token' => 'mock-token']);
        $service = new BitbucketService($integration);

        Http::fake([
            'api.bitbucket.org/2.0/user' => Http::response([
                'display_name' => 'Test User',
            ], 200),
            'api.bitbucket.org/2.0/user/emails' => Http::response([
                'values' => [
                    [
                        'email' => 'primary-unconfirmed@example.com',
                        'is_primary' => true,
                        'is_confirmed' => false,
                    ],
                ],
            ], 200),
        ]);

        $user = $service->getAuthenticatedUser();

        $this->assertNull($user['email']);
    }
}
