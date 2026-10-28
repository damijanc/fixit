<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Services\AI\Provider\ProviderRegistry;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Agent\InputProcessor\SystemPromptInputProcessor;

final class SymfonyAgentFactory implements AgentFactory
{
    public function __construct(
        private readonly ProviderRegistry $registry,
    ) {}

    public function create(?string $systemPrompt = null): AgentInterface
    {
        $model = trim($this->registry->modelId());

        if ($model === '') {
            throw AiRequestFailedException::noModel();
        }

        $inputProcessors = $systemPrompt !== null && trim($systemPrompt) !== ''
            ? [new SystemPromptInputProcessor($systemPrompt)]
            : [];

        return new Agent($this->registry->platform(), $model, $inputProcessors);
    }
}
