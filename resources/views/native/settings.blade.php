<scroll-view
    ref="settings-scroll"
    axis="vertical"
    class="h-full w-full bg-theme-background"
>
    <column
        ref="settings-screen"
        class="safe-area w-full px-4 pb-8"
    >
        <row ref="settings-top-bar" class="w-full items-center gap-1 py-1">
            <native:button
                ref="settings-back-button"
                variant="ghost"
                icon="arrow_back"
                a11y-label="Back to home"
                @tap="goBack"
            />
            <text ref="settings-title" class="flex-1 text-lg font-bold text-theme-on-background">
                Settings
            </text>
        </row>

        @if ($hasKey)
            <row ref="key-state" class="mt-2 w-full items-center gap-3 rounded-2xl bg-theme-primary-container p-4">
                <native:icon name="check_circle" size="22" color="#0F766E" dark-color="#5EEAD4" />
                <column class="flex-1">
                    <text class="text-sm font-bold text-theme-on-primary-container">Key saved</text>
                    <text class="text-xs text-theme-on-primary-container">FixIt is ready to use {{ $providerName }}.</text>
                </column>
            </row>
        @else
            <row ref="key-state" class="mt-2 w-full items-center gap-3 rounded-2xl bg-theme-warning-container p-4">
                <native:icon name="key" size="22" color="#B45309" dark-color="#FCD34D" />
                <column class="flex-1">
                    <text class="text-sm font-bold text-theme-on-warning-container">No key saved yet</text>
                    <text class="text-xs text-theme-on-warning-container">Add your {{ $providerName }} API key to start diagnosing.</text>
                </column>
            </row>
        @endif

        <column
            ref="provider-card"
            class="mt-4 w-full rounded-3xl bg-theme-surface p-5"
        >
            <text ref="provider-title" class="text-base font-bold text-theme-on-surface">
                AI provider
            </text>
            <text ref="settings-subtitle" class="mt-1 text-xs text-theme-on-surface-variant">
                One provider, one model and one key.
            </text>

            <native:select
                ref="provider-select"
                class="mt-4 w-full"
                label="Provider"
                native:model="provider"
                :options="$providerOptions"
                supporting="{{ $providerName }} · {{ $providerEndpoint }}"
                a11y-label="AI provider"
                a11y-hint="Selects which AI provider FixIt uses"
            />

            <native:select
                ref="model-select"
                class="mt-3 w-full"
                label="Model"
                native:model="model"
                :options="$modelOptions"
                supporting="{{ $modelLabel }} · vision-capable"
                a11y-label="AI model"
                a11y-hint="Selects a vision-capable model from the chosen provider"
            />

            <native:outlined-text-input
                ref="api-key-input"
                class="mt-3 w-full"
                label="{{ $providerName }} API key"
                placeholder="{{ $hasKey ? 'Paste a new key to replace the saved one' : 'Paste the full key' }}"
                secure
                revealable
                native:model.debounce.600ms="apiKey"
                a11y-label="{{ $providerName }} API key"
            />

            <text ref="key-hint" class="mt-2 text-xs text-theme-on-surface-variant">
                {{ $keyHint }}
            </text>

            <row class="mt-5 w-full gap-3">
                <native:button
                    ref="test-connection-button"
                    variant="secondary"
                    class="flex-1"
                    a11y-label="Test AI connection"
                    a11y-hint="Sends a short test prompt using the saved provider and key"
                    :loading="$testing"
                    @tap="testConnection"
                >
                    {{ $testing ? 'Testing…' : 'Test' }}
                </native:button>
                <native:button
                    ref="save-settings-button"
                    variant="primary"
                    class="flex-1"
                    :disabled="$testing"
                    a11y-label="Save provider settings"
                    a11y-hint="Stores the provider, model and key securely on this device"
                    @tap="saveSettings"
                >
                    Save
                </native:button>
            </row>

            @if ($status !== '')
                <text
                    ref="settings-status"
                    class="mt-4 w-full rounded-xl bg-theme-surface-variant p-3 text-sm text-theme-on-surface"
                    a11y-label="Settings result"
                >
                    {{ $status }}
                </text>
            @endif
        </column>

        <column ref="key-security" class="mt-4 w-full rounded-3xl border border-theme-outline p-5">
            <row class="w-full items-center gap-2">
                <native:icon name="shield" size="18" color="#5F625F" dark-color="#A1A09A" />
                <text class="flex-1 text-sm font-bold text-theme-on-background">How your key is stored</text>
            </row>
            <text ref="key-security-note" class="mt-2 text-xs leading-relaxed text-theme-on-surface-variant">
                The key is encrypted with the app key managed by Android Keystore and kept in app-private storage. It is only sent to the provider selected here, never to another provider, .env or the APK.
            </text>
            <text ref="provider-support-note" class="mt-2 text-xs leading-relaxed text-theme-on-surface-variant">
                Supported: DeepSeek, OpenRouter, Hugging Face, Anthropic and OpenAI. OpenRouter can use Claude and GPT models with one OpenRouter key.
            </text>
        </column>

        @if ($hasKey)
            <native:button
                ref="remove-key-button"
                variant="ghost"
                icon="delete"
                class="mt-4 w-full"
                a11y-label="Remove saved API key"
                a11y-hint="Deletes the provider key from this device"
                @tap="removeKey"
            >
                Remove saved key
            </native:button>
        @endif
    </column>
</scroll-view>
