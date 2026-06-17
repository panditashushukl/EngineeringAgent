<?php

namespace App\Services\Integrations;

use App\Services\Integrations\Bitbucket\BitbucketOAuthService;
use App\Services\Integrations\Github\GithubOAuthService;
use App\Services\Integrations\Gitlab\GitlabOAuthService;
use InvalidArgumentException;

class OAuthProviderFactory
{
    public static function make(string $provider): SourceControlOAuthProvider
    {
        return match ($provider) {
            'github' => app(GithubOAuthService::class),
            'gitlab' => app(GitlabOAuthService::class),
            'bitbucket' => app(BitbucketOAuthService::class),
            default => throw new InvalidArgumentException('Unsupported OAuth provider'),
        };
    }
}
