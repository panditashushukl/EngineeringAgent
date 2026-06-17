<?php

namespace App\Services\Integrations\Bitbucket;

use App\Services\Integrations\SourceControlOAuthProvider;
use Illuminate\Support\Facades\Http;

class BitbucketOAuthService implements SourceControlOAuthProvider
{
    public function getAuthorizationUrl(): string
    {
        $query = http_build_query([
            'client_id' => config('services.bitbucket.client_id'),
            'response_type' => 'code',
            'redirect_uri' => config('services.bitbucket.redirect'),
        ]);

        return "https://bitbucket.org/site/oauth2/authorize?{$query}";
    }

    public function exchangeCode(string $code): array
    {
        return Http::asForm()
            ->withBasicAuth(
                config('services.bitbucket.client_id'),
                config('services.bitbucket.client_secret')
            )
            ->post('https://bitbucket.org/site/oauth2/access_token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => config('services.bitbucket.redirect'),
            ])
            ->throw()
            ->json();
    }
}
