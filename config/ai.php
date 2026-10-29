<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | AI provider catalog
    |--------------------------------------------------------------------------
    |
    | FixIt ships a curated set of vision-capable models. The user selects one
    | provider and one model; only that provider's key is stored on device.
    |
    | Model IDs were checked against the live provider catalogs in October 2026
    | and against the Symfony AI 0.15 model catalogs. Keep this list small and
    | add only models verified to accept images.
    |
    */
    'default_provider' => env('FIXIT_AI_DEFAULT_PROVIDER', 'deepseek'),

    'providers' => [
        'deepseek' => [
            'name' => 'DeepSeek',
            'driver' => 'generic',
            'base_url' => rtrim((string) env('FIXIT_AI_BASE_URL', 'https://api.deepseek.com'), '/'),
            'key_hint' => 'Create a key at platform.deepseek.com. DeepSeek keys usually start with sk-.',
            'models' => [
                'deepseek-flash' => [
                    'label' => 'DeepSeek Flash',
                    'vision' => true,
                ],
            ],
        ],

        'openrouter' => [
            'name' => 'OpenRouter',
            'driver' => 'generic',
            'base_url' => rtrim((string) env('FIXIT_AI_OPENROUTER_BASE_URL', 'https://openrouter.ai/api'), '/'),
            'key_hint' => 'Create a key at openrouter.ai/keys. One OpenRouter key can use Claude and GPT models.',
            'models' => [
                'anthropic/claude-sonnet-4.5' => [
                    'label' => 'Claude Sonnet 4.5',
                    'vision' => true,
                ],
                'openai/gpt-4.1' => [
                    'label' => 'GPT-4.1',
                    'vision' => true,
                ],
                'openai/gpt-4o' => [
                    'label' => 'GPT-4o',
                    'vision' => true,
                ],
                'openai/gpt-5' => [
                    'label' => 'GPT-5',
                    'vision' => true,
                ],
            ],
        ],

        'huggingface' => [
            'name' => 'Hugging Face',
            'driver' => 'huggingface',
            'base_url' => rtrim((string) env('FIXIT_AI_HUGGINGFACE_BASE_URL', 'https://router.huggingface.co'), '/'),
            'key_hint' => 'Create a read token at huggingface.co/settings/tokens. Hugging Face tokens usually start with hf_.',
            // The router's automatic pick (novita) returns 503 for full-size
            // phone photos; deepinfra serves every model below and accepts them.
            'inference_provider' => env('FIXIT_AI_HUGGINGFACE_INFERENCE_PROVIDER', 'deepinfra'),
            'models' => [
                'Qwen/Qwen3-VL-30B-A3B-Instruct' => [
                    'label' => 'Qwen3-VL 30B',
                    'vision' => true,
                ],
                'Qwen/Qwen3.8-27B' => [
                    'label' => 'Qwen3.8 27B',
                    'vision' => true,
                ],
                'google/gemma-4-31B-it' => [
                    'label' => 'Gemma 4 31B',
                    'vision' => true,
                ],
            ],
        ],

        'anthropic' => [
            'name' => 'Anthropic',
            'driver' => 'anthropic',
            'base_url' => 'https://api.anthropic.com',
            'key_hint' => 'Create a key in the Anthropic Console. Anthropic keys usually start with sk-ant-.',
            // Current Claude models always think, and thinking shares max_tokens
            // with the answer: the bridge default of 1000 truncates replies.
            // Effort is set explicitly because the defaults differ per model.
            'models' => [
                'claude-opus-5-5' => [
                    'label' => 'Claude Opus 5.5',
                    'vision' => true,
                    'options' => ['max_tokens' => 16000, 'output_config' => ['effort' => 'medium']],
                ],
                'claude-sonnet-5-5' => [
                    'label' => 'Claude Sonnet 5.5',
                    'vision' => true,
                    'options' => ['max_tokens' => 16000, 'output_config' => ['effort' => 'medium']],
                ],
                'claude-haiku-5-5' => [
                    'label' => 'Claude Haiku 5.5',
                    'vision' => true,
                    'options' => ['max_tokens' => 16000, 'output_config' => ['effort' => 'medium']],
                ],
            ],
        ],

        'openai' => [
            'name' => 'OpenAI',
            'driver' => 'openai',
            'base_url' => 'https://api.openai.com',
            'key_hint' => 'Create a key at platform.openai.com/api-keys.',
            'models' => [
                'gpt-4.1' => [
                    'label' => 'GPT-4.1',
                    'vision' => true,
                ],
                'gpt-4o' => [
                    'label' => 'GPT-4o',
                    'vision' => true,
                ],
                'gpt-5' => [
                    'label' => 'GPT-5',
                    'vision' => true,
                ],
            ],
        ],
    ],

    'timeout' => (int) env('FIXIT_AI_TIMEOUT', 60),
];
