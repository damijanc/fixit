<?php

declare(strict_types=1);

return [
    /*
    | OpenAI-compatible endpoint (OpenAI, OpenRouter, Ollama, LM Studio, ...).
    | The generic Symfony AI bridge appends /v1/chat/completions.
    */
    'base_url' => rtrim((string) env('FIXIT_AI_BASE_URL', 'https://api.openai.com'), '/'),

    /*
    | Development-only key source. Release builds must use the in-app secure
    | storage flow (T8) instead; never commit a real key or bundle it in an APK.
    */
    'api_key' => env('FIXIT_AI_API_KEY'),

    'model' => env('FIXIT_AI_MODEL', 'gpt-4o-mini'),

    'timeout' => (int) env('FIXIT_AI_TIMEOUT', 30),
];
