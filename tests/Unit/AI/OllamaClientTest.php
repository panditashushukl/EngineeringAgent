<?php

namespace Tests\Unit\AI;

use App\Services\AI\OllamaClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaClientTest extends TestCase
{
    public function test_generate_sends_correct_payload_and_returns_response(): void
    {
        config(['services.ollama.base_url' => 'http://test-ollama:11434']);
        config(['services.ollama.model' => 'test-model']);

        Http::fake([
            'test-ollama:11434/api/generate' => Http::response([
                'response' => '{"summary": "Test Summary"}',
            ], 200)
        ]);

        $client = new OllamaClient();
        $result = $client->generate('Test prompt');

        $this->assertEquals('{"summary": "Test Summary"}', $result);

        Http::assertSent(function ($request) {
            return $request->url() === 'http://test-ollama:11434/api/generate'
                && $request->method() === 'POST'
                && $request['model'] === 'test-model'
                && $request['prompt'] === 'Test prompt'
                && $request['stream'] === false
                && $request['format'] === 'json';
        });
    }
}
