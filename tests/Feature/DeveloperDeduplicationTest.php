<?php

namespace Tests\Feature;

use App\Models\Developer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_or_create_for_provider_deduplication(): void
    {
        // 1. Create a developer with username/email but no external ID
        $dev1 = Developer::findOrCreateForProvider(
            provider: 'github',
            externalId: null,
            username: 'panditashushukl',
            email: 'panditashushukl@gmail.com',
            additionalData: ['name' => 'Ashutosh Shukla']
        );

        $this->assertDatabaseHas('developers', [
            'id' => $dev1->id,
            'username' => 'panditashushukl',
            'email' => 'panditashushukl@gmail.com',
            'external_id' => null,
        ]);

        // 2. Lookup again by external ID and username. It should find the existing one,
        // update the external ID, and NOT create a new developer.
        $dev2 = Developer::findOrCreateForProvider(
            provider: 'github',
            externalId: '93909296',
            username: 'panditashushukl',
            email: null,
            additionalData: ['avatar' => 'https://example.com/avatar.png']
        );

        $this->assertEquals($dev1->id, $dev2->id);
        $this->assertDatabaseHas('developers', [
            'id' => $dev1->id,
            'username' => 'panditashushukl',
            'email' => 'panditashushukl@gmail.com',
            'external_id' => '93909296',
            'avatar' => 'https://example.com/avatar.png',
        ]);
        $this->assertEquals(1, Developer::count());
    }

    public function test_find_or_create_by_email_matching(): void
    {
        $dev1 = Developer::create([
            'provider' => 'github',
            'username' => 'someuser',
            'email' => 'user@example.com',
            'name' => 'Some User',
        ]);

        // Look up using different username but matching email, should match the developer
        $dev2 = Developer::findOrCreateForProvider(
            provider: 'github',
            externalId: '123456',
            username: 'newusername',
            email: 'user@example.com'
        );

        $this->assertEquals($dev1->id, $dev2->id);
        $this->assertEquals('newusername', $dev2->username);
        $this->assertEquals('123456', $dev2->external_id);
        $this->assertEquals(1, Developer::count());
    }

    public function test_find_or_create_by_noreply_email_matching(): void
    {
        $dev1 = Developer::create([
            'provider' => 'github',
            'username' => 'panditashushukl',
            'email' => 'panditashushukl@gmail.com',
            'name' => 'Ashutosh Shukla',
        ]);

        // Look up using a noreply email containing the username, should match
        $dev2 = Developer::findOrCreateForProvider(
            provider: 'github',
            externalId: null,
            username: 'Ashutosh Shukla',
            email: '93909296+panditashushukl@users.noreply.github.com'
        );

        $this->assertEquals($dev1->id, $dev2->id);
        $this->assertEquals(1, Developer::count());
    }

    public function test_find_or_create_by_case_insensitive_username_matching(): void
    {
        $dev1 = Developer::create([
            'provider' => 'github',
            'username' => 'panditashushukl',
            'email' => 'panditashushukl@gmail.com',
            'name' => 'Ashutosh Shukla',
        ]);

        // Look up using different case for username and no email
        $dev2 = Developer::findOrCreateForProvider(
            provider: 'github',
            externalId: null,
            username: 'PanditAshuShukl',
            email: null
        );

        $this->assertEquals($dev1->id, $dev2->id);
        $this->assertEquals(1, Developer::count());
    }

    public function test_find_or_create_by_fuzzy_name_matching(): void
    {
        $dev1 = Developer::create([
            'provider' => 'github',
            'username' => 'panditashushukl',
            'email' => 'panditashushukl@gmail.com',
            'name' => 'Ashutosh_Shukla',
        ]);

        // Look up using different name format (spaces vs underscores vs case)
        $dev2 = Developer::findOrCreateForProvider(
            provider: 'github',
            externalId: null,
            username: 'ashutosh-shukla-temp',
            email: null,
            additionalData: ['name' => 'ashutosh shukla']
        );

        $this->assertEquals($dev1->id, $dev2->id);
        $this->assertEquals(1, Developer::count());
    }

    public function test_find_or_create_by_global_username_matching(): void
    {
        $dev1 = Developer::create([
            'provider' => 'github',
            'username' => 'panditashushukl',
            'email' => 'panditashushukl@gmail.com',
            'name' => 'Ashutosh Shukla',
        ]);

        // Look up under gitlab provider but matching username globally
        $dev2 = Developer::findOrCreateForProvider(
            provider: 'gitlab',
            externalId: null,
            username: 'panditashushukl',
            email: null
        );

        $this->assertEquals($dev1->id, $dev2->id);
        $this->assertEquals(1, Developer::count());
    }
}
