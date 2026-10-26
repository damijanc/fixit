<column
    ref="settings-screen"
    fill
    class="safe-area bg-theme-background px-6 py-6"
>
    <text ref="settings-title" font="accent" class="mt-4 text-3xl font-bold text-theme-on-background">
        Settings
    </text>
    <text ref="settings-subtitle" class="mt-1 text-sm text-theme-on-surface-variant">
        Provider credentials stay on this device.
    </text>

    <column
        ref="provider-card"
        class="mt-6 w-full rounded-2xl bg-theme-surface p-6 shadow-md"
    >
        <text ref="provider-title" class="text-lg font-bold text-theme-on-surface">
            {{ config('ai.provider_name') }}
        </text>
        <text ref="provider-model" class="mt-1 text-sm text-theme-on-surface-variant">
            Endpoint: {{ config('ai.base_url') }}
        </text>
        <text ref="provider-name" class="text-sm text-theme-on-surface-variant">
            Model: {{ config('ai.model') }}
        </text>

        <text ref="key-state" class="mt-4 text-sm text-theme-on-surface">
            {{ $hasKey ? 'A key is saved on this device.' : 'No key saved yet.' }}
        </text>

        <native:outlined-text-input
            ref="api-key-input"
            class="mt-4 w-full"
            label="DeepSeek API key"
            placeholder="sk-..."
            secure
            revealable
            native:model="apiKey"
            a11y-label="DeepSeek API key"
        />

        <native:button
            ref="save-key-button"
            variant="primary"
            class="mt-4 w-full"
            a11y-label="Save API key"
            a11y-hint="Stores the provider key securely on this device"
            @tap="saveKey"
        >
            Save key
        </native:button>

        <native:button
            ref="test-connection-button"
            class="mt-3 w-full"
            a11y-label="Test AI connection"
            a11y-hint="Sends a short test prompt using the saved key"
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
        Encrypted with the app key managed by Android Keystore and kept in app-private storage. The key is never written to .env or bundled in the APK.
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
