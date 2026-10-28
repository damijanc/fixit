<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\AI\Provider\PlatformFactory;
use App\Services\AI\Provider\ProviderDefinition;
use Symfony\AI\Platform\PlatformInterface;

final class FakePlatformFactory implements PlatformFactory
{
    /**
     * @var list<array{provider: string, key: string}>
     */
    public array $calls = [];

    public function __construct(private readonly ?PlatformInterface $platform = null) {}

    public function create(ProviderDefinition $provider, string $apiKey): PlatformInterface
    {
        $this->calls[] = ['provider' => $provider->id, 'key' => $apiKey];

        if ($this->platform === null) {
            throw new \RuntimeException('No fake platform was configured.');
        }

        return $this->platform;
    }
}
