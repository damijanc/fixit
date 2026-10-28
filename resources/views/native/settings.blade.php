<scroll-view
    ref="settings-scroll"
    axis="vertical"
    class="h-full w-full bg-theme-background"
>
    <column
        ref="settings-screen"
        class="safe-area w-full px-6 py-6"
    >
        <text ref="settings-title" font="accent" class="mt-4 text-3xl font-bold text-theme-on-background">
            Settings
        </text>
        <text ref="settings-subtitle" class="mt-1 text-sm text-theme-on-surface-variant">
            One provider, one model and one key. Credentials stay on this device.
        </text>

        <column
            ref="provider-card"
            class="mt-6 w-full rounded-2xl bg-theme-surface p-6 shadow-md"
        >
            <text ref="provider-title" class="text-lg font-bold text-theme-on-surface">
                AI provider
            </text>

            <native:select
                ref="provider-select"
                class="mt-4 w-full"
                label="Provider"
                native:model="provider"
                :options="$providerOptions"
                a11y-label="AI provider"
                a11y-hint="Selects which AI provider FixIt uses"
            />

            <text ref="provider-name" class="mt-1 text-sm text-theme-on-surface-variant">
                Selected: {{ $providerName }}
            </text>

            <native:select
                ref="model-select"
                class="mt-4 w-full"
                label="Model"
                native:model="model"
                :options="$modelOptions"
                supporting="{{ $modelLabel }} · vision-capable"
                a11y-label="AI model"
                a11y-hint="Selects a vision-capable model from the chosen provider"
            />

            <text ref="provider-endpoint" class="mt-2 text-xs text-theme-on-surface-variant">
                Endpoint: {{ $providerEndpoint }}
            </text>

            <text ref="key-state" class="mt-4 text-sm text-theme-on-surface">
                {{ $hasKey ? 'A key is saved for this provider.' : 'No key saved for this provider yet.' }}
            </text>

            <native:outlined-text-input
                ref="api-key-input"
                class="mt-4 w-full"
                label="{{ $providerName }} API key"
                placeholder="Paste the full key"
                secure
                revealable
                native:model="apiKey"
                a11y-label="{{ $providerName }} API key"
            />

            <text ref="key-hint" class="mt-2 text-xs text-theme-on-surface-variant">
                {{ $keyHint }}
            </text>

            <native:button
                ref="save-settings-button"
                variant="primary"
                class="mt-4 w-full"
                a11y-label="Save provider settings"
                a11y-hint="Stores the provider, model and key securely on this device"
                @tap="saveSettings"
            >
                Save settings
            </native:button>

            <native:button
                ref="test-connection-button"
                class="mt-3 w-full"
                a11y-label="Test AI connection"
                a11y-hint="Sends a short test prompt using the saved provider and key"
                @tap="testConnection"
            >
                Test connection
            </native:button>

            @if ($hasKey)
                <native:button
                    ref="remove-key-button"
                    variant="destructive"
                    class="mt-3 w-full"
                    a11y-label="Remove saved API key"
                    a11y-hint="Deletes the provider key from this device"
                    @tap="removeKey"
                >
                    Remove saved key
                </native:button>
            @endif

            @if ($status !== '')
                <text
                    ref="settings-status"
                    class="mt-4 text-sm text-theme-on-surface"
                    a11y-label="Settings result"
                >
                    {{ $status }}
                </text>
            @endif
        </column>

        <text ref="key-security-note" class="mt-5 text-xs text-theme-on-surface-variant">
            The key is encrypted with the app key managed by Android Keystore and kept in app-private storage. It is only sent to the provider selected here, never to another provider, .env or the APK.
        </text>

        <text ref="provider-support-note" class="mt-3 text-xs text-theme-on-surface-variant">
            Supported: DeepSeek, OpenRouter, Hugging Face, Anthropic and OpenAI. OpenRouter can use Claude and GPT models with one OpenRouter key.
        </text>

        <native:button
            ref="settings-back-button"
            class="mt-6 w-full"
            a11y-label="Back to home"
            @tap="goBack"
        >
            Back
        </native:button>
    </column>
</scroll-view>
