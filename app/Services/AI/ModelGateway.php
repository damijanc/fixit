<?php

declare(strict_types=1);

namespace App\Services\AI;

use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Bridge\Generic\Factory;
use Symfony\AI\Platform\Message\Content\Image;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\UserMessage;
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
    private const MAX_IMAGE_BYTES = 32 * 1024 * 1024;

    private const SUPPORTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

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
        return $this->run($prompt);
    }

    /**
     * Send a local image plus a text prompt to a vision-capable model.
     *
     * The image is base64-encoded by Symfony AI as an OpenAI-compatible
     * image_url data URL. Nothing is uploaded until the caller invokes this.
     *
     * @throws AiException
     */
    public function askWithImage(string $prompt, string $imagePath): string
    {
        $this->assertUsableImage($imagePath);

        return $this->run(new UserMessage(
            new Text($prompt),
            Image::fromFile($imagePath),
        ));
    }

    /**
     * Continue a text conversation with prior user/assistant turns.
     *
     * @throws AiException
     */
    public function continueConversation(MessageBag $messages): string
    {
        return $this->run($messages);
    }

    private function run(string|UserMessage|MessageBag $input): string
    {
        $model = $this->model ?? (string) config('ai.model');
        if (trim($model) === '') {
            throw AiRequestFailedException::noModel();
        }

        $platform = $this->platform ?? $this->platformFromConfig();

        try {
            $content = (new Agent($platform, $model))->call($input)->getContent();
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

    private function assertUsableImage(string $imagePath): void
    {
        if (! is_file($imagePath) || ! is_readable($imagePath)) {
            throw AiRequestFailedException::imageUnreadable();
        }

        $size = filesize($imagePath);
        if ($size === false || $size <= 0 || $size > self::MAX_IMAGE_BYTES) {
            throw AiRequestFailedException::imageTooLarge();
        }

        $mimeType = mime_content_type($imagePath);
        if (! is_string($mimeType) || ! in_array($mimeType, self::SUPPORTED_IMAGE_TYPES, true)) {
            throw AiRequestFailedException::imageUnsupported();
        }
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
