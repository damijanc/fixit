<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

use Symfony\AI\Platform\Bridge\Anthropic\Factory as AnthropicFactory;
use Symfony\AI\Platform\Bridge\Generic\Factory as GenericFactory;
use Symfony\AI\Platform\Bridge\HuggingFace\Factory as HuggingFaceFactory;
use Symfony\AI\Platform\Bridge\OpenAi\Factory as OpenAiFactory;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * The only class that knows which Symfony AI bridge a provider uses.
 *
 * Add a provider by adding a catalog entry and (if it has a dedicated bridge)
 * one branch here. Diagnosis services never import a provider bridge.
 */
final class SymfonyAiPlatformFactory implements PlatformFactory
{
    public function __construct(
        private readonly ?HttpClientInterface $httpClient = null,
        private readonly int $timeout = 60,
    ) {}

    public function create(ProviderDefinition $provider, #[\SensitiveParameter] string $apiKey): PlatformInterface
    {
        $httpClient = $this->httpClient ?? HttpClient::create([
            'timeout' => max(1, $this->timeout),
        ]);

        return match ($provider->driver) {
            ProviderDriver::Generic => GenericFactory::createPlatform(
                $provider->baseUrl,
                $apiKey,
                $httpClient,
                supportsEmbeddings: false,
                name: $provider->id,
            ),
            ProviderDriver::HuggingFace => HuggingFaceFactory::createPlatform(
                $apiKey,
                $provider->inferenceProvider,
                $httpClient,
                name: $provider->id,
                baseUrl: $provider->baseUrl,
            ),
            ProviderDriver::Anthropic => AnthropicFactory::createPlatform(
                $apiKey,
                $httpClient,
                name: $provider->id,
                baseUrl: $provider->baseUrl,
            ),
            ProviderDriver::OpenAI => OpenAiFactory::createPlatform(
                $apiKey,
                $httpClient,
                name: $provider->id,
            ),
        };
    }
}
