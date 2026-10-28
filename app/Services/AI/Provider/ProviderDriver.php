<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

enum ProviderDriver: string
{
    case Generic = 'generic';
    case HuggingFace = 'huggingface';
    case Anthropic = 'anthropic';
    case OpenAI = 'openai';

    public static function fromConfig(string $driver): self
    {
        return self::tryFrom($driver)
            ?? throw new \InvalidArgumentException("Unsupported AI provider driver [{$driver}].");
    }
}
