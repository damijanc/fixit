<?php

declare(strict_types=1);

namespace App\NativeComponents;

use App\Services\AI\AiException;
use App\Services\AI\ModelGateway;
use App\Services\AI\Provider\ProviderCatalog;
use App\Services\AI\Provider\ProviderDefinition;
use App\Services\AI\Provider\ProviderSettings;
use App\Services\AI\Provider\ProviderSettingsStore;
use Illuminate\View\View;
use Native\Mobile\Edge\NativeComponent;

class Settings extends NativeComponent
{
    public string $provider = '';

    public string $model = '';

    public string $apiKey = '';

    public string $status = '';

    public bool $hasKey = false;

    public string $providerName = '';

    public string $modelLabel = '';

    public string $keyHint = '';

    public string $savedProvider = '';

    public string $savedModel = '';

    public bool $savedHasKey = false;

    public function mount(): void
    {
        $store = app(ProviderSettingsStore::class);
        $settings = $store->get();
        $catalog = app(ProviderCatalog::class);
        $provider = $catalog->get($settings->providerId);

        if (! $provider instanceof ProviderDefinition) {
            $provider = $catalog->defaultProvider();
            $this->status = 'The previously selected provider is no longer available. Choose a provider and model.';
        }

        $model = $provider->model($settings->modelId);

        if ($model === null) {
            $model = $provider->defaultModel();
            $this->status = 'The previously selected model is no longer available. Choose a model.';
        }

        $this->provider = $provider->id;
        $this->model = $model->id;
        $this->savedProvider = $provider->id;
        $this->savedModel = $model->id;
        $this->hasKey = $settings->hasApiKey();
        $this->savedHasKey = $this->hasKey;
        $this->refreshLabels();
    }

    public function updatingProvider(string $providerId): void
    {
        if ($providerId !== $this->provider) {
            // A key typed for the previous provider must not be saved for the
            // newly selected provider by accident.
            $this->apiKey = '';
        }
    }

    public function updatedProvider(string $providerId): void
    {
        $provider = app(ProviderCatalog::class)->get($providerId);

        if (! $provider instanceof ProviderDefinition) {
            $this->status = 'Choose a provider from the list.';

            return;
        }

        $this->provider = $provider->id;

        if ($provider->model($this->model) === null) {
            $this->model = $provider->defaultModel()->id;
        }

        $this->hasKey = $provider->id === $this->savedProvider && $this->savedHasKey;
        $this->refreshLabels();

        if ($provider->id !== $this->savedProvider) {
            $this->status = "Enter an API key for {$provider->name}. The previous provider's key is not reused.";
        }
    }

    public function updatedModel(string $modelId): void
    {
        $provider = app(ProviderCatalog::class)->get($this->provider);

        if (! $provider instanceof ProviderDefinition || $provider->model($modelId) === null) {
            $this->status = 'Choose a model from the list.';

            return;
        }

        $this->model = $modelId;
        $this->refreshLabels();

        if ($this->provider === $this->savedProvider && $modelId !== $this->savedModel) {
            $this->status = 'Save settings to use this model.';
        }
    }

    public function saveSettings(): void
    {
        $catalog = app(ProviderCatalog::class);
        $provider = $catalog->get($this->provider);

        if (! $provider instanceof ProviderDefinition) {
            $this->status = 'Choose a provider from the list.';

            return;
        }

        $model = $provider->model($this->model);

        if ($model === null) {
            $this->status = "Choose a model for {$provider->name}.";

            return;
        }

        $key = trim($this->apiKey);
        $providerChanged = $provider->id !== $this->savedProvider;

        if ($key === '' && $providerChanged) {
            $this->status = "Enter an API key for {$provider->name} before saving. The previous provider's key is not reused.";

            return;
        }

        if ($key !== '' && ! $this->isPlausibleKey($key)) {
            $this->status = 'That API key does not look complete. Paste the full key without spaces.';

            return;
        }

        $store = app(ProviderSettingsStore::class);

        if (! $store->save(new ProviderSettings($provider->id, $model->id, $key === '' ? null : $key))) {
            $this->status = 'Could not save provider settings in secure storage. Try again.';

            return;
        }

        $saved = $store->get();
        $this->apiKey = '';
        $this->savedProvider = $saved->providerId;
        $this->savedModel = $saved->modelId;
        $this->savedHasKey = $saved->hasApiKey();
        $this->hasKey = $this->savedHasKey;
        $this->refreshLabels();

        $this->status = $saved->hasApiKey()
            ? 'Provider settings and API key saved securely on this device.'
            : "Settings saved. Enter an API key for {$provider->name} before testing the connection.";
    }

    public function removeKey(): void
    {
        if (! app(ProviderSettingsStore::class)->forgetKey()) {
            $this->status = 'Could not remove the saved key. Try again.';

            return;
        }

        $this->apiKey = '';
        $this->hasKey = false;
        $this->savedHasKey = false;
        $this->status = 'Saved API key removed from this device.';
    }

    public function testConnection(): void
    {
        if ($this->provider !== $this->savedProvider || $this->model !== $this->savedModel) {
            $this->status = 'Save provider settings before testing the connection.';

            return;
        }

        $store = app(ProviderSettingsStore::class);

        if (! $store->get()->hasApiKey()) {
            $this->status = "Save an API key for {$this->providerName} before testing the connection.";

            return;
        }

        $this->status = "Contacting {$this->providerName}…";

        try {
            $reply = app(ModelGateway::class)->ask('Reply with exactly: FixIt AI connectivity OK');
            $this->status = 'Connected: '.$reply;
        } catch (AiException $exception) {
            $this->status = $exception->getMessage();
        } catch (\Throwable $exception) {
            report($exception);
            $this->status = 'Unexpected error while contacting the model.';
        }
    }

    public function goBack(): void
    {
        $this->back();
    }

    public function render(): View
    {
        $catalog = app(ProviderCatalog::class);
        $provider = $catalog->get($this->provider);

        return view('native.settings', [
            'providerOptions' => array_keys($catalog->providers()),
            'modelOptions' => $provider instanceof ProviderDefinition ? array_keys($provider->models) : [],
            'providerEndpoint' => $provider?->baseUrl ?? '',
        ]);
    }

    private function refreshLabels(): void
    {
        $provider = app(ProviderCatalog::class)->get($this->provider);
        $model = $provider?->model($this->model);

        $this->providerName = $provider?->name ?? $this->provider;
        $this->modelLabel = $model?->label ?? $this->model;
        $this->keyHint = $provider?->keyHint ?? '';
    }

    private function isPlausibleKey(string $key): bool
    {
        // Provider formats differ (sk-, sk-ant-, sk-or-v1-, hf_, JWT tokens…).
        // Only reject clearly incomplete input; the provider is the authority.
        return mb_strlen($key) >= 8 && preg_match('/\s/', $key) !== 1;
    }
}
