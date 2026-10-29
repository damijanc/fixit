<?php

declare(strict_types=1);

use App\Services\AI\Provider\ProviderCatalog;
use Tests\TestCase;

uses(TestCase::class);

it('exposes the curated providers and models', function () {
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));

    expect(array_keys($catalog->providers()))
        ->toBe(['deepseek', 'openrouter', 'huggingface', 'anthropic', 'openai']);

    expect($catalog->defaultProviderId())->toBe('deepseek');
    expect($catalog->defaultProvider()->name)->toBe('DeepSeek');
    expect($catalog->defaultModel()->id)->toBe('deepseek-flash');
    expect($catalog->defaultModel()->supportsVision)->toBeTrue();
});

it('offers Claude and GPT models through the single OpenRouter provider', function () {
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));
    $openrouter = $catalog->get('openrouter');

    expect($openrouter)->not->toBeNull();
    expect(array_keys($openrouter->models))->toContain('anthropic/claude-sonnet-4.5');
    expect(array_keys($openrouter->models))->toContain('openai/gpt-4.1');
    expect(array_keys($openrouter->models))->toContain('openai/gpt-4o');
    expect(array_keys($openrouter->models))->toContain('openai/gpt-5');

    foreach ($openrouter->models as $model) {
        expect($model->supportsVision)->toBeTrue();
    }
});

it('offers verified vision models for Hugging Face, Anthropic and OpenAI', function () {
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));

    expect(array_keys($catalog->get('huggingface')->models))
        ->toContain('Qwen/Qwen3-VL-30B-A3B-Instruct')
        ->toContain('Qwen/Qwen3.8-27B')
        ->toContain('google/gemma-4-31B-it');

    expect(array_keys($catalog->get('anthropic')->models))
        ->toContain('claude-opus-5-5')
        ->toContain('claude-sonnet-5-5')
        ->toContain('claude-haiku-5-5');

    expect(array_keys($catalog->get('openai')->models))
        ->toContain('gpt-4.1')
        ->toContain('gpt-4o')
        ->toContain('gpt-5');
});

it('returns null for an unknown provider instead of switching silently', function () {
    $catalog = ProviderCatalog::fromConfig((array) config('ai'));

    expect($catalog->has('unknown'))->toBeFalse();
    expect($catalog->get('unknown'))->toBeNull();
});

it('rejects a catalog with an unconfigured default provider', function () {
    expect(fn () => ProviderCatalog::fromConfig([
        'default_provider' => 'missing',
        'providers' => [],
    ]))->toThrow(InvalidArgumentException::class, 'Default AI provider [missing] is not configured.');
});
