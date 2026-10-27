<?php

declare(strict_types=1);

namespace App\Services\AI;

use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Bridge\Generic\Factory;
use Symfony\AI\Platform\PlatformInterface;
use Symfony\Component\HttpClient\HttpClient;

/**
 * Thin application boundary around Symfony AI.
 *
 * The platform is injectable so tests never touch the network. The provider
 * key always comes from on-device secure storage (T8); no key is read from
 * .env or bundled config.
 */
final class ModelGateway
{
    public function __construct(
        private readonly ApiKeyStore $keyStore,
        private readonly ?PlatformInterface $platform = null,
        private readonly ?string $model = null,
    ) {}

    /**
     * @throws AiException
     */
    public function ask(string $prompt): string
    {
        $model = $this->model ?? (string) config('ai.model');
        if (trim($model) === '') {
            throw AiRequestFailedException::noModel();
        }

        $platform = $this->platform ?? $this->platformFromConfig();

        try {
            $content = (new Agent($platform, $model))->call($prompt)->getContent();
        } catch (AiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            logger()->warning('AI request failed', [
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);

            throw AiRequestFailedException::fromProvider($exception);
        }

        if (! is_string($content) || trim($content) === '') {
            throw AiRequestFailedException::emptyResponse();
        }

        return trim($content);
    }

    private function platformFromConfig(): PlatformInterface
    {
        $apiKey = $this->keyStore->get();
        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw MissingApiKeyException::forProvider();
        }

        $baseUrl = trim((string) config('ai.base_url'));
        if ($baseUrl === '') {
            throw AiRequestFailedException::unreachable(new \RuntimeException('Missing AI base URL.'));
        }

        $timeout = max(1, (int) config('ai.timeout', 60));

        return Factory::createPlatform(
            $baseUrl,
            $apiKey,
            HttpClient::create(['timeout' => $timeout]),
        );
    }
}
