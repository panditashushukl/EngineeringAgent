<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A guest is redirected to the login page.
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    /**
     * An authenticated user can access the dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertStatus(200);
    }

    /**
     * A guest is redirected to the login page when accessing sub-routes.
     */
    public function test_guest_is_redirected_to_login_on_sub_routes(): void
    {
        $response = $this->get('/repositories');
        $response->assertRedirect('/login');

        $response2 = $this->get('/settings');
        $response2->assertRedirect('/login');
    }

    /**
     * An authenticated user can access SPA sub-routes.
     */
    public function test_authenticated_user_can_access_sub_routes(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/repositories');
        $response->assertStatus(200);

        $response2 = $this->actingAs($user)->get('/settings');
        $response2->assertStatus(200);
    }
}
