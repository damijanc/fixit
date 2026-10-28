<?php

declare(strict_types=1);

use App\Services\Diagnosis\ConversationBuilder;
use App\Services\Repair\RepairSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\UserMessage;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * @return list<array{0: string, 1: string}>
 */
function conversationTurns(iterable $messages): array
{
    $turns = [];

    foreach ($messages as $message) {
        /** @var AssistantMessage|UserMessage $message */
        $turns[] = [$message->getRole()->value, (string) $message->asText()];
    }

    return $turns;
}

it('replays the photo analysis and earlier answers in order', function () {
    $service = new RepairSessionService;
    $session = $service->startWithPhoto('/tmp/photo.jpg', 'no water flow');
    $service->recordVisionResult($session, 'photo request', 'possible pump fault');
    $service->recordFollowUp($session, 'the light is on', 'check the filter');

    $messages = (new ConversationBuilder)->forFollowUp($session->refresh(), 'the filter is clean');

    expect(conversationTurns($messages->getMessages()))->toBe([
        ['user', 'photo request'],
        ['assistant', 'possible pump fault'],
        ['user', 'the light is on'],
        ['assistant', 'check the filter'],
        ['user', 'the filter is clean'],
    ]);
});

it('leaves the system prompt to the agent', function () {
    $service = new RepairSessionService;
    $session = $service->startWithPhoto('/tmp/photo.jpg');
    $service->recordVisionResult($session, 'photo request', 'possible pump fault');

    $messages = (new ConversationBuilder)->forFollowUp($session->refresh(), 'answer');

    expect($messages->getSystemMessage())->toBeNull();
});
