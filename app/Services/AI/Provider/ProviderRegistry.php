<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

use App\Services\AI\AiException;
use App\Services\AI\AiRequestFailedException;
use App\Services\AI\MissingApiKeyException;
use App\Services\AI\ProviderConfigurationException;
use App\Services\AI\UnsupportedModelCapabilityException;
use Symfony\AI\Platform\PlatformInterface;

/**
 * Resolves the saved provider/model and lazily constructs the Symfony AI
 * platform. This is the only AI service the diagnosis workflow needs.
 */
final class ProviderRegistry
{
    public function __construct(
        private readonly ProviderCatalog $catalog,
        private readonly ProviderSettingsStore $settingsStore,
        private readonly PlatformFactory $platformFactory,
    ) {}

    public function settings(): ProviderSettings
    {
        return $this->settingsStore->get();
    }

    public function providerId(): string
    {
        return $this->settings()->providerId;
    }

    public function provider(): ProviderDefinition
    {
        $providerId = $this->settings()->providerId;
        $provider = $this->catalog->get($providerId);

        if (! $provider instanceof ProviderDefinition) {
            throw ProviderConfigurationException::unknownProvider($providerId);
        }

        return $provider;
    }

    public function providerName(): string
    {
        try {
            return $this->provider()->name;
        } catch (ProviderConfigurationException) {
            return 'your AI provider';
        }
    }

    public function model(): ModelDefinition
    {
        $provider = $this->provider();
        $modelId = $this->settings()->modelId;
        $model = $provider->model($modelId);

        if (! $model instanceof ModelDefinition) {
            throw ProviderConfigurationException::unknownModel($modelId, $provider->name);
        }

        return $model;
    }

    public function modelId(): string
    {
        return $this->model()->id;
    }

    public function assertVisionSupported(): void
    {
        $model = $this->model();

        if (! $model->supportsImage()) {
            throw UnsupportedModelCapabilityException::noVision($model->id, $this->provider()->name);
        }
    }

    public function platform(): PlatformInterface
    {
        $provider = $this->provider();
        $settings = $this->settings();

        if (! $settings->hasApiKey()) {
            throw MissingApiKeyException::forProvider($provider->name);
        }

        try {
            return $this->platformFactory->create($provider, trim((string) $settings->apiKey));
        } catch (AiException $exception) {
            throw $exception;
        } catch (\InvalidArgumentException $exception) {
            throw AiRequestFailedException::invalidKey($provider->name, $exception);
        }
    }
}
