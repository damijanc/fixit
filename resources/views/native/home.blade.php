<column
    ref="home-screen"
    fill
    class="safe-area bg-theme-background px-6 py-6"
>
    <text ref="app-title" font="accent" class="mt-6 text-3xl font-bold text-theme-on-background">
        FixIt
    </text>
    <text ref="app-subtitle" class="mt-1 text-base text-theme-on-surface-variant">
        AI-guided repair assistant
    </text>

    <column
        ref="ai-card"
        class="mt-8 w-full rounded-2xl bg-theme-surface p-6 shadow-md"
    >
        <text ref="ai-title" class="text-lg font-bold text-theme-on-surface">
            AI connectivity
        </text>
        <text ref="ai-description" class="mt-2 text-sm text-theme-on-surface-variant">
            Checks that the embedded PHP runtime can reach your OpenAI-compatible model through Symfony AI.
        </text>

        <native:button
            ref="test-ai-button"
            variant="primary"
            class="mt-5 w-full"
            a11y-label="Test AI connection"
            a11y-hint="Sends a short test prompt to the configured model"
            @tap="testAiConnection"
        >
            Test AI connection
        </native:button>

        @if ($aiStatus !== '')
            <text
                ref="ai-status"
                class="mt-4 text-sm text-theme-on-surface"
                a11y-label="AI connection result"
            >
                {{ $aiStatus }}
            </text>
        @endif

        <native:button
            ref="open-settings-button"
            class="mt-4 w-full"
            a11y-label="Provider settings"
            a11y-hint="Set or remove the provider API key"
            @tap="openSettings"
        >
            Provider settings
        </native:button>
    </column>

    <column
        ref="camera-card"
        class="mt-6 w-full rounded-2xl bg-theme-surface p-6 shadow-md"
    >
        <text ref="camera-title" class="text-lg font-bold text-theme-on-surface">
            Camera check
        </text>
        <text ref="camera-description" class="mt-2 text-sm text-theme-on-surface-variant">
            Opens the native camera. Photos stay in app-private storage on this device.
        </text>

        <native:button
            ref="take-photo-button"
            class="mt-5 w-full"
            a11y-label="Take a photo"
            a11y-hint="Opens the camera to capture a test photo"
            @tap="takePhoto"
        >
            Take a photo
        </native:button>

        <text ref="camera-hint" class="mt-3 text-xs text-theme-on-surface-variant">
            In the camera app, take the photo and tap the ✓ checkmark to confirm. FixIt only receives the photo after that.
        </text>

        @if ($photoPath !== '')
            <image
                ref="captured-photo"
                src="{{ $photoPath }}"
                alt="Captured repair photo"
                class="mt-4 h-48 w-full rounded-xl object-cover"
            />
        @endif

        @if ($cameraStatus !== '')
            <text
                ref="camera-status"
                class="mt-4 text-sm text-theme-on-surface"
                a11y-label="Camera result"
            >
                {{ $cameraStatus }}
            </text>
        @endif
    </column>

    <text ref="home-footer" class="mt-auto pt-8 text-center text-xs text-theme-on-surface-variant">
        Android-only · Local sessions · No account required
    </text>
</column>
