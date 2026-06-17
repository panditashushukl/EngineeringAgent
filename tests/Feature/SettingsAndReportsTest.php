<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Setting;
use App\Models\WorkflowReport;
use App\Models\Developer;
use App\Models\DeveloperMetric;
use App\Models\Integration;
use App\Models\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_guest_cannot_access_settings_and_reports()
    {
        $this->getJson('/api/settings')->assertStatus(401);
        $this->postJson('/api/settings', [])->assertStatus(401);
        $this->postJson('/api/settings/recalculate', [])->assertStatus(401);
        $this->getJson('/api/workflow-report')->assertStatus(401);
        $this->postJson('/api/workflow-report/generate', [])->assertStatus(401);
    }

    public function test_user_can_get_default_settings()
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/settings')
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'settings' => [
                    'ai_provider',
                    'gemini_model',
                    'ollama_base_url',
                    'ollama_model',
                    'weight_task_completion',
                    'weight_reviews',
                    'weight_delivery',
                    'weight_code_quality',
                ]
            ]);

        $this->assertEquals('gemini', $response->json('settings.ai_provider'));
        $this->assertEquals('http://localhost:11434', $response->json('settings.ollama_base_url'));
        $this->assertEquals(0.40, $response->json('settings.weight_task_completion'));
    }

    public function test_user_can_save_settings_with_valid_weights()
    {
        $this->actingAs($this->user)
            ->postJson('/api/settings', [
                'ai_provider' => 'gemini',
                'gemini_model' => 'gemini-2.5-pro',
                'ollama_base_url' => 'http://localhost:9999',
                'ollama_model' => 'codellama:latest',
                'weight_task_completion' => 0.25,
                'weight_reviews' => 0.25,
                'weight_delivery' => 0.25,
                'weight_code_quality' => 0.25,
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $this->assertEquals('gemini', Setting::get('ai_provider'));
        $this->assertEquals('http://localhost:9999', Setting::get('ollama_base_url'));
        $this->assertEquals(0.25, (float) Setting::get('weight_task_completion'));
    }

    public function test_user_cannot_save_settings_with_invalid_weights_sum()
    {
        $this->actingAs($this->user)
            ->postJson('/api/settings', [
                'ai_provider' => 'ollama',
                'gemini_model' => 'gemini-2.5-pro',
                'ollama_base_url' => 'http://localhost:9999',
                'ollama_model' => 'codellama:latest',
                'weight_task_completion' => 0.20,
                'weight_reviews' => 0.20,
                'weight_delivery' => 0.20,
                'weight_code_quality' => 0.20, // sum is 0.80
            ])
            ->assertStatus(422)
            ->assertJson([
                'success' => false
            ]);
    }

    public function test_user_can_recalculate_metrics()
    {
        $developer = Developer::factory()->create();
        
        $metric = DeveloperMetric::create([
            'developer_id' => $developer->id,
            'period_start' => now()->subDays(30)->toDateString(),
            'period_end' => now()->toDateString(),
            'commits' => 10,
            'prs_created' => 2,
            'prs_merged' => 2,
            'reviews_done' => 5,
            'bugs_fixed' => 1,
            'deployments' => 2,
            'task_completion_score' => 80.0,
            'code_quality_score' => 80.0,
            'review_score' => 80.0,
            'delivery_speed_score' => 80.0,
            'developer_score' => 80.0,
        ]);

        $this->actingAs($this->user)
            ->postJson('/api/settings/recalculate')
            ->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        $metric->refresh();
        $this->assertNotNull($metric->developer_score);
    }

    public function test_user_can_get_latest_and_generate_workflow_report()
    {
        // Get latest (should be null)
        $this->actingAs($this->user)
            ->getJson('/api/workflow-report')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'report' => null
            ]);

        // Mock LLMService
        $this->mock(\App\Services\AI\LLMService::class, function ($mock) {
            $mock->shouldReceive('ask')
                ->once()
                ->andReturn(json_encode([
                    'executive_summary' => 'This is a mocked AI Workflow & Velocity Report summary.',
                    'velocity' => 'Commits and velocity rates are high.',
                    'collaboration' => 'Strong peer reviews.',
                    'delivery' => 'Stable deployment cadence.',
                    'recommendations' => [
                        'Mock Recommendation 1',
                        'Mock Recommendation 2'
                    ]
                ]));
        });

        // Generate report
        $this->actingAs($this->user)
            ->postJson('/api/workflow-report/generate')
            ->assertStatus(200)
            ->assertJson([
                'success' => true
            ])
            ->assertJsonStructure([
                'report' => [
                    'id',
                    'report_text',
                    'metrics_snapshot',
                    'created_at',
                ]
            ]);

        // Get latest (should be the newly generated report)
        $this->actingAs($this->user)
            ->getJson('/api/workflow-report')
            ->assertStatus(200)
            ->assertJsonPath('report.report_text', function ($val) {
                $decoded = json_decode($val, true);
                return isset($decoded['executive_summary']) && str_contains($decoded['executive_summary'], 'AI Workflow & Velocity Report');
            });
    }

    public function test_user_can_sync_integration()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $integration = Integration::create([
            'user_id' => $this->user->id,
            'provider' => 'github',
            'access_token' => 'mock-token',
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/integrations/{$integration->id}/sync")
            ->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SyncRepositoriesJob::class);
    }

    public function test_user_can_sync_repository()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $integration = Integration::create([
            'user_id' => $this->user->id,
            'provider' => 'github',
            'access_token' => 'mock-token',
        ]);
        $repository = Repository::create([
            'integration_id' => $integration->id,
            'external_id' => '12345',
            'provider' => 'github',
            'owner' => 'test-owner',
            'name' => 'test-repo',
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/repositories/{$repository->id}/sync")
            ->assertStatus(200)
            ->assertJson([
                'success' => true
            ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SyncCommitsJob::class);
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SyncPullRequestsJob::class);
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SyncTasksJob::class);
        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SyncDeploymentsJob::class);
    }

    public function test_user_can_generate_developer_insights()
    {
        \Illuminate\Support\Facades\Queue::fake();

        $developer = Developer::factory()->create();

        $this->actingAs($this->user)
            ->postJson("/api/developers/{$developer->id}/generate-insights")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Insight generation queued'
            ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\GenerateDeveloperInsightsJob::class);
    }

    public function test_user_can_generate_all_insights()
    {
        \Illuminate\Support\Facades\Queue::fake();

        Developer::factory()->count(2)->create();

        $this->actingAs($this->user)
            ->postJson("/api/insights/generate-all")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Insight generation queued for all developers'
            ]);

        \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\GenerateDeveloperInsightsJob::class, 2);
    }
}

