<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Welcome Back');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $response = $this->postJson('/login', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Invalid credentials',
        ]);
    }

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->postJson('/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Login successful',
        ]);

        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_session_and_redirects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_logout_all_devices_invalidates_all_sessions_and_tokens(): void
    {
        $user = User::factory()->create();

        // Create some Sanctum tokens
        $user->createToken('token-1');
        $user->createToken('token-2');
        $this->assertEquals(2, $user->tokens()->count());

        $response = $this->actingAs($user)->post('/logout-all-devices');

        $response->assertRedirect('/login');
        $this->assertGuest();
        $this->assertEquals(0, $user->tokens()->count());
    }
}
