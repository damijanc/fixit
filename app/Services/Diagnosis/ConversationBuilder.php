<?php

declare(strict_types=1);

namespace App\Services\Diagnosis;

use App\Models\RepairSession;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\UserMessage;

/**
 * Rebuilds a repair session as a Symfony AI conversation.
 *
 * The photo itself is not re-sent: the follow-up uses the stored text of the
 * photo request and the model's analysis, which keeps token costs small. The
 * system prompt is added by the agent, not here.
 */
final class ConversationBuilder
{
    public function forFollowUp(RepairSession $session, string $answer): MessageBag
    {
        $messages = new MessageBag;

        $prompt = $session->observations()
            ->where('kind', 'photo_prompt')
            ->latest('id')
            ->value('content');

        if (is_string($prompt) && $prompt !== '') {
            $messages->add(new UserMessage(new Text($prompt)));
        }

        if (is_string($session->summary) && $session->summary !== '') {
            $messages->add(new AssistantMessage(new Text($session->summary)));
        }

        $history = $session->observations()
            ->whereIn('kind', ['follow_up_answer', 'follow_up_result'])
            ->orderBy('id')
            ->get();

        foreach ($history as $observation) {
            $content = new Text((string) $observation->content);

            $messages->add(
                $observation->role === 'assistant'
                    ? new AssistantMessage($content)
                    : new UserMessage($content),
            );
        }

        $messages->add(new UserMessage(new Text($answer)));

        return $messages;
    }
}
