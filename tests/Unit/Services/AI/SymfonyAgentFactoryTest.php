<?php

declare(strict_types=1);

use App\Services\AI\AiRequestFailedException;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderRegistry;
use App\Services\AI\Provider\ProviderSettings;
use App\Services\AI\SymfonyAgentFactory;
use Symfony\AI\Agent\Agent;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Tests\Support\FakePlatformFactory;
use Tests\Support\InMemoryProviderSettingsStore;
use Tests\TestCase;

uses(TestCase::class);

function agentFactoryFor(string $provider, string $model, array $config): SymfonyAgentFactory
{
    return new SymfonyAgentFactory(new ProviderRegistry(
        ProviderCatalog::fromConfig($config),
        new InMemoryProviderSettingsStore(new ProviderSettings($provider, $model, 'test-key')),
        new FakePlatformFactory(MockPlatformFactory::createPlatform('ok')),
    ));
}

it('builds an agent for the saved model', function () {
    $agent = agentFactoryFor('deepseek', 'deepseek-flash', (array) config('ai'))->create();

    expect($agent)->toBeInstanceOf(Agent::class)
        ->and($agent->getModel())->toBe('deepseek-flash');
});

it('refuses to build an agent without a model id', function () {
    $factory = agentFactoryFor('test', '', [
        'default_provider' => 'test',
        'providers' => [
            'test' => [
                'name' => 'Test Provider',
                'driver' => 'generic',
                'base_url' => 'https://example.test',
                'models' => ['' => ['label' => 'Blank']],
            ],
        ],
    ]);

    expect(fn () => $factory->create())->toThrow(AiRequestFailedException::class, 'No AI model is configured');
});
