<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\AI\ModelGateway;
use App\Services\AI\Provider\PlatformFactory;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderRegistry;
use App\Services\AI\Provider\ProviderSettings;
use Symfony\AI\Platform\PlatformInterface;

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
        $catalog ??= ProviderCatalog::fromConfig((array) config('ai'));
        $registry = new ProviderRegistry(
            $catalog,
            new InMemoryProviderSettingsStore(new ProviderSettings($provider, $model, $key)),
            $factory ?? new FakePlatformFactory($platform),
        );

        return new ModelGateway($registry, $platform, $model);
    }
}
