<?php

namespace Tests\Unit\AI;

use App\Models\Developer;
use App\Models\DeveloperMetric;
use App\Services\AI\DeveloperInsightService;
use App\Services\AI\LLMService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DeveloperInsightServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_generate_stores_decoded_json_insights(): void
    {
        $developer = Developer::factory()->create(['name' => 'John Doe']);
        $metric = DeveloperMetric::factory()->create([
            'developer_id' => $developer->id,
            'developer_score' => 85.00,
        ]);

        $mockLLM = Mockery::mock(LLMService::class);
        $mockLLM->shouldReceive('ask')
            ->once()
            ->andReturn(json_encode([
                'summary' => 'Great performance.',
                'strengths' => ['Fast delivery'],
                'weaknesses' => ['Minor review delay'],
                'risks' => ['None'],
                'recommendations' => ['Keep it up']
            ]));

        $this->app->instance(LLMService::class, $mockLLM);

        $service = $this->app->make(DeveloperInsightService::class);
        $insight = $service->generate($developer);

        $this->assertDatabaseHas('developer_insights', [
            'developer_id' => $developer->id,
            'summary' => 'Great performance.',
        ]);

        $this->assertEquals(['Fast delivery'], $insight->strengths);
        $this->assertEquals(['Minor review delay'], $insight->weaknesses);
        $this->assertEquals(['None'], $insight->risks);
        $this->assertEquals(['Keep it up'], $insight->recommendations);
    }

    public function test_generate_handles_markdown_wrapped_json(): void
    {
        $developer = Developer::factory()->create(['name' => 'Jane Smith']);
        $metric = DeveloperMetric::factory()->create([
            'developer_id' => $developer->id,
        ]);

        $mockLLM = Mockery::mock(LLMService::class);
        $mockLLM->shouldReceive('ask')
            ->once()
            ->andReturn("```json\n{\n  \"summary\": \"Improving steadily.\",\n  \"strengths\": [\"Code quality\"],\n  \"weaknesses\": [],\n  \"risks\": [],\n  \"recommendations\": []\n}\n```");

        $this->app->instance(LLMService::class, $mockLLM);

        $service = $this->app->make(DeveloperInsightService::class);
        $insight = $service->generate($developer);

        $this->assertDatabaseHas('developer_insights', [
            'developer_id' => $developer->id,
            'summary' => 'Improving steadily.',
        ]);

        $this->assertEquals(['Code quality'], $insight->strengths);
    }

    public function test_generate_falls_back_to_raw_response_when_json_is_invalid(): void
    {
        $developer = Developer::factory()->create(['name' => 'Alice Johnson']);
        $metric = DeveloperMetric::factory()->create([
            'developer_id' => $developer->id,
        ]);

        $mockLLM = Mockery::mock(LLMService::class);
        $mockLLM->shouldReceive('ask')
            ->once()
            ->andReturn("This is a plain text summary without JSON.");

        $this->app->instance(LLMService::class, $mockLLM);

        $service = $this->app->make(DeveloperInsightService::class);
        $insight = $service->generate($developer);

        $this->assertDatabaseHas('developer_insights', [
            'developer_id' => $developer->id,
            'summary' => 'This is a plain text summary without JSON.',
        ]);

        $this->assertEmpty($insight->strengths);
        $this->assertEmpty($insight->weaknesses);
    }
}
