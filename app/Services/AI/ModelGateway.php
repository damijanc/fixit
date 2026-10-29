<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Provider\ProviderRegistry;
use Psr\Log\LoggerInterface;
use Symfony\AI\Platform\FinishReason\FinishReason;
use Symfony\AI\Platform\FinishReason\FinishReasonCase;
use Symfony\AI\Platform\Message\Content\Image;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\Result\MultiPartResult;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Result\TextResult;

/**
 * Thin application boundary around a Symfony AI agent.
 *
 * Tests replace the provider through PlatformFactory, so this class always
 * runs the same path as production. The provider and key always come from
 * on-device secure storage; no key is read from .env or bundled config.
 */
final class ModelGateway
{
    private const MAX_IMAGE_BYTES = 32 * 1024 * 1024;

    private const SUPPORTED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct(
        private readonly AgentFactory $agents,
        private readonly ProviderRegistry $registry,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @throws AiException
     */
    public function ask(string $prompt, ?string $systemPrompt = null): string
    {
        return $this->run($prompt, $systemPrompt);
    }

    /**
     * Send a local image plus a text prompt to a vision-capable model.
     *
     * Symfony AI maps the image to the provider's multimodal image format.
     * Nothing is uploaded until the caller invokes this.
     *
     * @throws AiException
     */
    public function askWithImage(string $prompt, string $imagePath, ?string $systemPrompt = null): string
    {
        $this->registry->assertVisionSupported();
        $this->assertUsableImage($imagePath);

        return $this->run(new UserMessage(
            new Text($prompt),
            Image::fromFile($imagePath),
        ), $systemPrompt);
    }

    /**
     * Continue a text conversation with prior user/assistant turns.
     *
     * @throws AiException
     */
    public function continueConversation(MessageBag $messages, ?string $systemPrompt = null): string
    {
        return $this->run($messages, $systemPrompt);
    }

    /**
     * Display name of the selected provider, safe to show in the UI.
     */
    public function providerName(): string
    {
        return $this->registry->providerName();
    }

    private function run(string|UserMessage|MessageBag $input, ?string $systemPrompt): string
    {
        $agent = $this->agents->create($systemPrompt);

        try {
            $result = $agent->call($input, $this->registry->model()->options)->getResult();
        } catch (AiException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $providerName = $this->registry->providerName();

            $this->logger->warning('AI request failed', [
                'provider' => $providerName,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
            ]);

            throw AiRequestFailedException::fromProvider($exception, $providerName);
        }

        $finishReason = $result->getMetadata()->get('finish_reason');

        if ($finishReason instanceof FinishReason && $finishReason->is(FinishReasonCase::CONTENT_FILTER)) {
            throw AiRequestFailedException::refused($this->registry->providerName());
        }

        $content = self::text($result);

        if ($content === null || trim($content) === '') {
            throw AiRequestFailedException::emptyResponse();
        }

        return trim($content);
    }

    /**
     * The answer text. Models that think (current Claude models always do)
     * return the thinking block next to the text as a multi-part result.
     */
    private static function text(ResultInterface $result): ?string
    {
        return match (true) {
            $result instanceof MultiPartResult => $result->asText(),
            $result instanceof TextResult => $result->getContent(),
            default => null,
        };
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
}
