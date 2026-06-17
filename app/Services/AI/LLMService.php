<?php

namespace App\Services\AI;

class LLMService
{
    public function __construct(
        protected OllamaClient $ollamaClient,
        protected GeminiClient $geminiClient
    ) {}

    public function ask(
        string $prompt
    ): string {
        $provider = \App\Models\Setting::get(
            'ai_provider',
            config('services.ai_provider', 'gemini')
        );

        if ($provider === 'gemini') {
            return $this->geminiClient->generate(
                $prompt
            );
        }

        return $this->ollamaClient->generate(
            $prompt
        );
    }
}