<?php

declare(strict_types=1);

use App\Services\AI\ModelGateway;
use Native\Mobile\Testing\Native;
use Symfony\AI\Platform\Test\MockPlatformFactory;

it('renders the FixIt home screen', function () {
    Native::visit('/')
        ->assertSee('FixIt')
        ->assertSee('AI connectivity')
        ->assertSee('Test AI connection')
        ->assertSee('Android-only · Local sessions · No account required');
});

it('shows the model reply after a successful connectivity check', function () {
    app()->instance(ModelGateway::class, new ModelGateway(
        MockPlatformFactory::createPlatform('FixIt AI connectivity OK'),
        'test-model',
    ));

    Native::visit('/')
        ->tap('test-ai-button')
        ->assertSee('OK: FixIt AI connectivity OK')
        ->assertElement('text', fn (array $node): bool => ($node['ref'] ?? null) === 'ai-status');
});

it('shows a safe message when no api key is configured', function () {
    config(['ai.api_key' => null]);

    Native::visit('/')
        ->tap('test-ai-button')
        ->assertSee('No API key configured');
});
