<?php

declare(strict_types=1);

namespace App\Services\AI\Provider;

/**
 * Curated provider/model catalog.
 *
 * This is configuration, not diagnosis logic: adding a provider only changes
 * this catalog and the platform factory.
 */
final class ProviderCatalog
{
    /**
     * @param  array<string, ProviderDefinition>  $providers
     */
    public function __construct(
        private readonly array $providers,
        private readonly string $defaultProviderId,
    ) {
        if (! isset($this->providers[$this->defaultProviderId])) {
            throw new \InvalidArgumentException("Default AI provider [{$this->defaultProviderId}] is not configured.");
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(array $config): self
    {
        $providers = [];

        foreach ((array) ($config['providers'] ?? []) as $id => $providerConfig) {
            if (! is_string($id) || ! is_array($providerConfig)) {
                continue;
            }

            $models = [];

            foreach ((array) ($providerConfig['models'] ?? []) as $modelId => $modelConfig) {
                if (! is_string($modelId) || ! is_array($modelConfig)) {
                    continue;
                }

                $models[$modelId] = new ModelDefinition(
                    id: $modelId,
                    label: (string) ($modelConfig['label'] ?? $modelId),
                    supportsVision: (bool) ($modelConfig['vision'] ?? true),
                );
            }

            $providers[$id] = new ProviderDefinition(
                id: $id,
                name: (string) ($providerConfig['name'] ?? $id),
                driver: ProviderDriver::fromConfig((string) ($providerConfig['driver'] ?? 'generic')),
                baseUrl: rtrim((string) ($providerConfig['base_url'] ?? ''), '/'),
                keyHint: (string) ($providerConfig['key_hint'] ?? ''),
                models: $models,
                inferenceProvider: ($providerConfig['inference_provider'] ?? '') !== ''
                    ? (string) $providerConfig['inference_provider']
                    : null,
            );
        }

        return new self(
            $providers,
            (string) ($config['default_provider'] ?? 'deepseek'),
        );
    }

    /**
     * @return array<string, ProviderDefinition>
     */
    public function providers(): array
    {
        return $this->providers;
    }

    public function has(string $id): bool
    {
        return isset($this->providers[$id]);
    }

    public function get(string $id): ?ProviderDefinition
    {
        return $this->providers[$id] ?? null;
    }

    public function defaultProvider(): ProviderDefinition
    {
        return $this->providers[$this->defaultProviderId];
    }

    public function defaultModel(): ModelDefinition
    {
        return $this->defaultProvider()->defaultModel();
    }

    public function defaultProviderId(): string
    {
        return $this->defaultProviderId;
    }
}
