<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class OllamaClient
{
    protected string $baseUrl;

    protected string $model;

    public function __construct()
    {
        $this->baseUrl = \App\Models\Setting::get('ollama_base_url', config('services.ollama.base_url', 'http://localhost:11434'));

        $this->model = \App\Models\Setting::get(
            'ollama_model',
            config('services.ollama.model', 'llama3.1:8b')
        );
    }

    public function generate(
        string $prompt,
        array $options = []
    ): string {

        $response = Http::timeout(120)
            ->post(
                "{$this->baseUrl}/api/generate",
                [
                    'model' => $this->model,
                    'prompt' => $prompt,
                    'stream' => false,
                    'format' => 'json',
                ]
            )
            ->throw()
            ->json();

        return data_get(
            $response,
            'response',
            ''
        );
    }
}
