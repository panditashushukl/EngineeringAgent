<?php

namespace Tests\Unit\AI;

use App\Services\AI\GeminiClient;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeminiClientTest extends TestCase
{
    public function test_generate_sends_correct_payload_and_returns_response(): void
    {
        config(['services.gemini.api_key' => 'test-api-key']);
        config(['services.gemini.model' => 'gemini-2.5-pro']);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => '{"summary": "Test Summary"}']
                            ]
                        ]
                    ]
                ]
            ], 200)
        ]);

        $client = new GeminiClient();
        $result = $client->generate('Test prompt');

        $this->assertEquals('{"summary": "Test Summary"}', $result);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return str_contains($request->url(), 'generativelanguage.googleapis.com')
                && str_contains($request->url(), 'key=test-api-key')
                && $request->method() === 'POST'
                && data_get($data, 'contents.0.parts.0.text') === 'Test prompt'
                && data_get($data, 'generationConfig.responseMimeType') === 'application/json';
        });
    }

    public function test_generate_throws_exception_if_api_key_is_missing(): void
    {
        config(['services.gemini.api_key' => '']);

        $client = new GeminiClient();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Gemini API key is not configured.');

        $client->generate('Test prompt');
    }
}
