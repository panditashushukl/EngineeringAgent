<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class GeminiClient
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key', env('GEMINI_API_KEY', ''));
        $this->model = \App\Models\Setting::get('gemini_model', config('services.gemini.model', 'gemini-2.5-pro'));
    }

    public function generate(
        string $prompt,
        array $options = []
    ): string {
        if (empty($this->apiKey)) {
            throw new \Exception("Gemini API key is not configured.");
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}";

        $response = Http::timeout(120)
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'responseMimeType' => 'application/json'
                ]
            ])
            ->throw()
            ->json();

        return data_get(
            $response,
            'candidates.0.content.parts.0.text',
            ''
        );
    }
}
