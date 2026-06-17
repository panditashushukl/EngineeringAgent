<?php

namespace App\Services\Integrations;

interface SourceControlOAuthProvider
{
    public function getAuthorizationUrl(): string;

    public function exchangeCode(string $code): array;
}
