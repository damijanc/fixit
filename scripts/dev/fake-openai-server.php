<?php

declare(strict_types=1);

/**
 * Minimal OpenAI-compatible server for local/emulator smoke tests.
 *
 * Run from the repository root:
 *   php -S 0.0.0.0:8099 scripts/dev/fake-openai-server.php
 *
 * On the Android emulator the host is reachable at 10.0.2.2, so use:
 *   FIXIT_AI_BASE_URL=http://10.0.2.2:8099
 *
 * The key is never checked here. Use a throwaway value such as "test-key".
 */
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || $path !== '/v1/chat/completions') {
    http_response_code(404);
    header('Content-Type: application/json');

    echo json_encode(['error' => ['message' => 'Not found']]);

    return;
}

$body = json_decode((string) file_get_contents('php://input'), true);
$model = is_array($body) && is_string($body['model'] ?? null) ? $body['model'] : 'fixit-fake';

header('Content-Type: application/json');

echo json_encode([
    'id' => 'chatcmpl_fixit_smoke',
    'object' => 'chat.completion',
    'created' => time(),
    'model' => $model,
    'choices' => [[
        'index' => 0,
        'message' => [
            'role' => 'assistant',
            'content' => 'FixIt AI connectivity OK',
        ],
        'finish_reason' => 'stop',
    ]],
    'usage' => [
        'prompt_tokens' => 10,
        'completion_tokens' => 5,
        'total_tokens' => 15,
    ],
], JSON_UNESCAPED_SLASHES);
