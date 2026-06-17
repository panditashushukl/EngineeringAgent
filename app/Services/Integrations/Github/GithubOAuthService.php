<?php

namespace App\Services\Integrations\Github;

use App\Services\Integrations\SourceControlOAuthProvider;
use Illuminate\Support\Facades\Http;

class GithubOAuthService implements SourceControlOAuthProvider
{
    public function getAuthorizationUrl(): string
    {
        $query = http_build_query([
            'client_id' => config('services.github.client_id'),
            'redirect_uri' => config('services.github.redirect'),
            'scope' => 'repo read:user user:email',
            'allow_signup' => 'true',
        ]);

        return "https://github.com/login/oauth/authorize?{$query}";
    }

    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->post('https://github.com/login/oauth/access_token', [
                'client_id' => config('services.github.client_id'),
                'client_secret' => config('services.github.client_secret'),
                'code' => $code,
                'redirect_uri' => config('services.github.redirect'),
            ]);

        if ($response->failed()) {
            return [
                'error' => 'github_oauth_failed',
                'error_description' => $response->body(),
            ];
        }

        return $response->json();
    }
}
