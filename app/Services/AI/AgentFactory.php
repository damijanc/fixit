<?php

declare(strict_types=1);

namespace App\Services\AI;

use Symfony\AI\Agent\AgentInterface;

/**
 * Builds the Symfony AI agent for the provider and model saved in Settings.
 *
 * A new agent is built per request because the user can switch provider or
 * model at runtime. FixIt uses exactly one agent with one model at a time:
 * no toolbox, memory or multi-agent orchestration.
 */
interface AgentFactory
{
    /**
     * @throws AiException when no usable provider, model or key is configured
     */
    public function create(?string $systemPrompt = null): AgentInterface;
}
