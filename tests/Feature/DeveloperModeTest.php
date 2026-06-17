<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperModeTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_developer_endpoints()
    {
        $this->getJson('/api/developer/tokens')->assertStatus(401);
        $this->postJson('/api/developer/tokens')->assertStatus(401);
        $this->deleteJson('/api/developer/tokens/1')->assertStatus(401);
    }

    public function test_user_can_list_and_generate_developer_tokens()
    {
        // 1. Initially user should have no tokens
        $response = $this->actingAs($this->user)
            ->getJson('/api/developer/tokens')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'tokens' => []
            ]);

        // 2. Generate a token
        $genResponse = $this->actingAs($this->user)
            ->postJson('/api/developer/tokens')
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'token',
                'token_id',
                'created_at'
            ]);

        $this->assertTrue($genResponse->json('success'));
        $plainTextToken = $genResponse->json('token');
        $this->assertNotEmpty($plainTextToken);

        // 3. List tokens again, should show 1 token
        $listResponse = $this->actingAs($this->user)
            ->getJson('/api/developer/tokens')
            ->assertStatus(200);

        $tokens = $listResponse->json('tokens');
        $this->assertCount(1, $tokens);
        $this->assertEquals('Developer Key', $tokens[0]['name']);
    }

    public function test_generating_new_token_does_not_revoke_old_ones()
    {
        // Generate first token
        $this->actingAs($this->user)->postJson('/api/developer/tokens');

        // Generate second token
        $this->actingAs($this->user)->postJson('/api/developer/tokens');

        // List should have 2 active developer keys
        $response = $this->actingAs($this->user)->getJson('/api/developer/tokens');
        $this->assertCount(2, $response->json('tokens'));
    }

    public function test_user_can_revoke_developer_token()
    {
        $genResponse = $this->actingAs($this->user)->postJson('/api/developer/tokens');
        $tokenId = $genResponse->json('token_id');

        // Revoke token
        $this->actingAs($this->user)
            ->deleteJson("/api/developer/tokens/{$tokenId}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        // List should now be empty
        $response = $this->actingAs($this->user)->getJson('/api/developer/tokens');
        $this->assertCount(0, $response->json('tokens'));
    }

    public function test_generated_token_authenticates_sanctum_routes()
    {
        // Generate token
        $genResponse = $this->actingAs($this->user)->postJson('/api/developer/tokens');
        $plainTextToken = $genResponse->json('token');

        // Access api route with Bearer token
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $plainTextToken,
            'Accept' => 'application/json'
        ])
        ->getJson('/api/engineering-agent/dashboard/overview')
        ->assertStatus(200)
        ->assertJsonStructure([
            'repositories',
            'developers',
            'commits',
            'pull_requests',
            'average_score'
        ]);
    }
}
