<?php

declare(strict_types=1);

return [
    /*
    | DeepSeek's OpenAI-compatible endpoint. The generic Symfony AI bridge
    | appends /v1/chat/completions; DeepSeek also serves /chat/completions,
    | so the bridge talks to https://api.deepseek.com/v1/chat/completions.
    |
    | Override with any OpenAI-compatible provider if needed.
    */
    'base_url' => rtrim((string) env('FIXIT_AI_BASE_URL', 'https://api.deepseek.com'), '/'),

    /*
    | deepseek-flash accepts text and images through the same chat endpoint.
    */
    'model' => env('FIXIT_AI_MODEL', 'deepseek-flash'),

    'provider_name' => env('FIXIT_AI_PROVIDER_NAME', 'DeepSeek'),

    'key_prefix' => env('FIXIT_AI_KEY_PREFIX', 'sk-'),

    'timeout' => (int) env('FIXIT_AI_TIMEOUT', 60),
];
