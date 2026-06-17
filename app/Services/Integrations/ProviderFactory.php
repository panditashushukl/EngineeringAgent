<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Services\Integrations\Contracts\SourceControlProvider;
use App\Services\Integrations\Github\GithubService;
use App\Services\Integrations\Gitlab\GitlabService;
use App\Services\Integrations\Bitbucket\BitbucketService;
use InvalidArgumentException;

class ProviderFactory
{
    public static function make(
        Integration $integration
    ): SourceControlProvider {

        return match ($integration->provider) {

            'github' =>
                app(
                    GithubService::class,
                    ['integration' => $integration]
                ),

            'gitlab' =>
                app(
                    GitlabService::class,
                    ['integration' => $integration]
                ),

            'bitbucket' =>
                app(
                    BitbucketService::class,
                    ['integration' => $integration]
                ),

            default =>
                throw new InvalidArgumentException(
                    'Unsupported provider'
                ),
        };
    }
}