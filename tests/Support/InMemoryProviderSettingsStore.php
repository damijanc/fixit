<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Services\AI\Provider\ProviderSettings;
use App\Services\AI\Provider\ProviderSettingsStore;

final class InMemoryProviderSettingsStore implements ProviderSettingsStore
{
    public function __construct(private ProviderSettings $settings = new ProviderSettings('deepseek', 'deepseek-flash')) {}

    public function get(): ProviderSettings
    {
        return $this->settings;
    }

    public function save(ProviderSettings $settings): bool
    {
        $apiKey = $settings->apiKey;

        if (! $settings->hasApiKey() && $settings->providerId === $this->settings->providerId) {
            $apiKey = $this->settings->apiKey;
        }

        $this->settings = new ProviderSettings($settings->providerId, $settings->modelId, $apiKey);

        return true;
    }

    public function forgetKey(): bool
    {
        $this->settings = $this->settings->withoutApiKey();

        return true;
    }
}
