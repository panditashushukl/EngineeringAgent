<?php

namespace App\Services\Integrations\Gitlab;

use App\Services\Integrations\SourceControlOAuthProvider;
use Illuminate\Support\Facades\Http;

class GitlabOAuthService implements SourceControlOAuthProvider
{
    public function getAuthorizationUrl(): string
    {
        $baseUrl = config('services.gitlab.base_url');
        $baseUrl = str_replace('/api/v4', '', $baseUrl);
        
        $query = http_build_query([
            'client_id' => config('services.gitlab.client_id'),
            'redirect_uri' => config('services.gitlab.redirect'),
            'response_type' => 'code',
            'scope' => 'read_api read_user read_repository',
        ]);

        return "{$baseUrl}/oauth/authorize?{$query}";
    }

    public function exchangeCode(string $code): array
    {
        $baseUrl = config('services.gitlab.base_url');
        $baseUrl = str_replace('/api/v4', '', $baseUrl);
        
        $response = Http::asForm()
            ->acceptJson()
            ->post("{$baseUrl}/oauth/token", [
                'client_id' => config('services.gitlab.client_id'),
                'client_secret' => config('services.gitlab.client_secret'),
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => config('services.gitlab.redirect'),
            ]);

        if ($response->failed()) {
            return [
                'error' => 'gitlab_oauth_failed',
                'error_description' => $response->body(),
            ];
        }

        return $response->json();
    }
}
