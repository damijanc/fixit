<?php

declare(strict_types=1);

use App\Services\AI\AiRequestFailedException;
use App\Services\AI\MissingApiKeyException;
use App\Services\AI\ModelGateway;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\SystemMessage;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\ModelCatalog\FallbackModelCatalog;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Provider;
use Symfony\AI\Platform\Test\MockModelClient;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Symfony\AI\Platform\Test\MockResultConverter;
use Tests\Support\InMemoryApiKeyStore;
use Tests\TestCase;

uses(TestCase::class);

it('returns model text through the gateway', function () {
    $platform = MockPlatformFactory::createPlatform('FixIt AI connectivity OK');

    $gateway = new ModelGateway(new InMemoryApiKeyStore('test-key'), $platform, 'test-model');

    expect($gateway->ask('ping'))->toBe('FixIt AI connectivity OK');
});

it('sends the prompt through Symfony AI', function () {
    $client = new MockModelClient('pong');
    $platform = new Platform([
        new Provider('mock', [$client], [new MockResultConverter], new FallbackModelCatalog),
    ]);

    $gateway = new ModelGateway(new InMemoryApiKeyStore('test-key'), $platform, 'test-model');
    $gateway->ask('ping');

    expect($client->getCalls())->toHaveCount(1);
    expect($client->getCalls()[0]['model']->getName())->toBe('test-model');
});

it('fails safely when the secure key store is empty', function () {
    $gateway = new ModelGateway(new InMemoryApiKeyStore);

    expect(fn () => $gateway->ask('ping'))->toThrow(MissingApiKeyException::class);
});

it('fails safely when no model is configured', function () {
    config(['ai.model' => '']);

    $platform = MockPlatformFactory::createPlatform('unused');
    $gateway = new ModelGateway(new InMemoryApiKeyStore('test-key'), $platform);

    expect(fn () => $gateway->ask('ping'))->toThrow(AiRequestFailedException::class);
});

it('wraps provider failures without leaking provider details', function () {
    $platform = MockPlatformFactory::createPlatform(static function (): never {
        throw new RuntimeException('secret provider response body');
    });

    $gateway = new ModelGateway(new InMemoryApiKeyStore('test-key'), $platform, 'test-model');

    try {
        $gateway->ask('ping');
    } catch (AiRequestFailedException $exception) {
        expect($exception->getMessage())
            ->not->toContain('secret provider response body')
            ->toContain('could not be reached');

        return;
    }

    $this->fail('Expected an AiRequestFailedException.');
});

it('surfaces the provider HTTP status without leaking the response body', function () {
    $platform = MockPlatformFactory::createPlatform(static function (): never {
        throw new RuntimeException('Unauthorized: secret upstream body', 401);
    });

    $gateway = new ModelGateway(new InMemoryApiKeyStore('test-key'), $platform, 'test-model');

    try {
        $gateway->ask('ping');
    } catch (AiRequestFailedException $exception) {
        expect($exception->getMessage())
            ->toContain('401')
            ->not->toContain('secret upstream body');

        return;
    }

    $this->fail('Expected an AiRequestFailedException.');
});

it('sends an image as a base64 data URL for vision', function () {
    $client = new MockModelClient('I see a small blue test image.');
    $platform = new Platform([
        new Provider('mock', [$client], [new MockResultConverter], new FallbackModelCatalog),
    ]);

    $gateway = new ModelGateway(new InMemoryApiKeyStore('test-key'), $platform, 'deepseek-flash');

    $reply = $gateway->askWithImage(
        'What is in this photo?',
        base_path('tests/Fixtures/vision-photo.jpg'),
    );

    expect($reply)->toBe('I see a small blue test image.');
    expect($client->getCalls())->toHaveCount(1);

    $payload = json_encode($client->getCalls()[0]['payload']);

    expect($payload)
        ->toContain('image_url')
        ->toContain('data:image')
        ->toContain('base64,')
        ->toContain('What is in this photo?');
});

it('rejects an unreadable image before sending it', function () {
    $gateway = new ModelGateway(new InMemoryApiKeyStore('test-key'), MockPlatformFactory::createPlatform('unused'), 'deepseek-flash');

    expect(fn () => $gateway->askWithImage('What is this?', '/does/not/exist.jpg'))
        ->toThrow(AiRequestFailedException::class, 'could not be read');
});

it('sends prior turns when continuing a conversation', function () {
    $client = new MockModelClient('Next step: check the filter.');
    $platform = new Platform([
        new Provider('mock', [$client], [new MockResultConverter], new FallbackModelCatalog),
    ]);

    $gateway = new ModelGateway(new InMemoryApiKeyStore('test-key'), $platform, 'deepseek-flash');

    $reply = $gateway->continueConversation(new MessageBag(
        new SystemMessage('safety rules'),
        new UserMessage(new Text('earlier photo prompt')),
        new AssistantMessage(new Text('earlier assistant result')),
        new UserMessage(new Text('the light is on but there is no water')),
    ));

    expect($reply)->toBe('Next step: check the filter.');

    $payload = json_encode($client->getCalls()[0]['payload']);

    expect($payload)
        ->toContain('earlier assistant result')
        ->toContain('the light is on but there is no water');
});
