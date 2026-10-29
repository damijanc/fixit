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
use Symfony\AI\Platform\TokenUsage\TokenUsageInterface;

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
    public function askWithImage(string $prompt, string $imagePath, ?string $systemPrompt = null, ?array $schema = null): string
    {
        $this->registry->assertVisionSupported();
        $this->assertUsableImage($imagePath);

        return $this->run(new UserMessage(
            new Text($prompt),
            Image::fromFile($imagePath),
        ), $systemPrompt, $schema);
    }

    /**
     * Continue a text conversation with prior user/assistant turns.
     *
     * @param  array<string, mixed>|null  $schema  See supportsStructuredOutput()
     *
     * @throws AiException
     */
    public function continueConversation(MessageBag $messages, ?string $systemPrompt = null, ?array $schema = null): string
    {
        return $this->run($messages, $systemPrompt, $schema);
    }

    /**
     * Whether a $schema passed to the calls above is sent: the selected model
     * then answers with JSON matching it. Other models get no schema and
     * answer in free text, so callers must handle both.
     */
    public function supportsStructuredOutput(): bool
    {
        return $this->registry->model()->supportsStructuredOutput;
    }

    /**
     * Display name of the selected provider, safe to show in the UI.
     */
    public function providerName(): string
    {
        return $this->registry->providerName();
    }

    /**
     * @param  array<string, mixed>|null  $schema
     */
    private function run(string|UserMessage|MessageBag $input, ?string $systemPrompt, ?array $schema = null): string
    {
        $agent = $this->agents->create($systemPrompt);
        $options = $this->registry->model()->options;

        // A plain JSON schema array: the Anthropic bridge sends it as
        // output_config.format and the reply stays JSON text.
        if ($schema !== null && $this->supportsStructuredOutput()) {
            $options['response_format'] = [
                'type' => 'json_schema',
                'json_schema' => ['name' => 'diagnosis', 'schema' => $schema, 'strict' => true],
            ];
        }

        try {
            $result = $agent->call($input, $options)->getResult();
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

        $this->logUsage($result);

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
     * Token counts only, never content: cost per diagnosis and whether prompt
     * caching hits (cache_read_tokens > 0 on follow-ups).
     */
    private function logUsage(ResultInterface $result): void
    {
        $usage = $result->getMetadata()->get('token_usage');

        if (! $usage instanceof TokenUsageInterface) {
            return;
        }

        $this->logger->info('AI usage', [
            'provider' => $this->registry->providerName(),
            'model' => $this->registry->modelId(),
            'input_tokens' => $usage->getPromptTokens(),
            'output_tokens' => $usage->getCompletionTokens(),
            'cache_read_tokens' => $usage->getCacheReadTokens(),
            'cache_creation_tokens' => $usage->getCacheCreationTokens(),
        ]);
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
