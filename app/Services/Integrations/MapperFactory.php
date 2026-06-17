<?php

namespace App\Services\Integrations;

use App\Services\Integrations\Github\GithubMapper;
use App\Services\Integrations\Gitlab\GitlabMapper;
use App\Services\Integrations\Bitbucket\BitbucketMapper;
use InvalidArgumentException;

class MapperFactory
{
    public static function make(string $provider): string
    {
        return match (strtolower($provider)) {
            'github' => GithubMapper::class,
            'gitlab' => GitlabMapper::class,
            'bitbucket' => BitbucketMapper::class,
            default => throw new InvalidArgumentException("Unsupported provider: {$provider}"),
        };
    }
}
