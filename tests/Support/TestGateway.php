<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\AI\ModelGateway;
use App\Services\AI\Provider\PlatformFactory;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderRegistry;
use App\Services\AI\Provider\ProviderSettings;
use App\Services\AI\SymfonyAgentFactory;
use Psr\Log\LoggerInterface;
use Symfony\AI\Platform\PlatformInterface;

/**
 * Builds the production gateway wiring with a mocked Symfony AI platform.
 */
final class TestGateway
{
    public static function make(
        PlatformInterface $platform,
        string $provider = 'deepseek',
        string $model = 'deepseek-flash',
        ?string $key = 'test-key',
        ?ProviderCatalog $catalog = null,
        ?PlatformFactory $factory = null,
    ): ModelGateway {
        $registry = new ProviderRegistry(
            $catalog ?? ProviderCatalog::fromConfig((array) config('ai')),
            new InMemoryProviderSettingsStore(new ProviderSettings($provider, $model, $key)),
            $factory ?? new FakePlatformFactory($platform),
        );

        return new ModelGateway(new SymfonyAgentFactory($registry), $registry, app(LoggerInterface::class));
    }
}
