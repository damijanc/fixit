<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

use Symfony\AI\Platform\PlatformInterface;

interface PlatformFactory
{
    public function create(ProviderDefinition $provider, #[\SensitiveParameter] string $apiKey): PlatformInterface;
}
