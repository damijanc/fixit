<scroll-view
    ref="home-scroll"
    axis="vertical"
    class="h-full w-full bg-theme-background"
>
    <column
        ref="home-screen"
        class="safe-area w-full px-6 py-6"
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
                Repair photo
            </text>
            <text ref="camera-description" class="mt-2 text-sm text-theme-on-surface-variant">
                Opens the native camera. The photo stays on this device until you choose to send it.
            </text>

            <native:button
                ref="take-photo-button"
                class="mt-5 w-full"
                a11y-label="Take a photo"
                a11y-hint="Opens the camera to capture a repair photo"
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

                <native:outlined-text-input
                    ref="symptoms-input"
                    class="mt-4 w-full"
                    label="Describe the symptoms"
                    placeholder="e.g. no water flow, strange noise…"
                    multiline
                    native:model="symptoms"
                    a11y-label="Symptoms"
                />

                <text ref="upload-consent" class="mt-4 text-xs text-theme-on-surface-variant">
                    Sending uploads this photo and symptoms to DeepSeek for analysis. Do not send photos containing people, documents or personal information.
                </text>

                <native:button
                    ref="analyze-photo-button"
                    variant="primary"
                    class="mt-3 w-full"
                    a11y-label="Send photo to DeepSeek"
                    a11y-hint="Uploads the photo to DeepSeek for a cautious diagnosis"
                    @tap="analyzePhoto"
                >
                    {{ $visionBusy ? 'Sending to DeepSeek…' : 'Send photo to DeepSeek' }}
                </native:button>

                <native:button
                    ref="discard-photo-button"
                    variant="destructive"
                    class="mt-2 w-full"
                    a11y-label="Discard photo"
                    a11y-hint="Deletes the captured photo from this device"
                    @tap="discardPhoto"
                >
                    Discard photo
                </native:button>
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

            @if ($sessionNotice !== '')
                <text
                    ref="session-notice"
                    class="mt-2 text-xs text-theme-on-surface-variant"
                    a11y-label="Session status"
                >
                    {{ $sessionNotice }}
                </text>
            @endif

            @if ($visionStatus !== '')
                <text
                    ref="vision-status"
                    class="mt-4 text-sm text-theme-on-surface"
                    a11y-label="Vision result"
                >
                    {{ $visionStatus }}
                </text>
            @endif

            @if ($visionResult !== '')
                <column
                    ref="vision-result-card"
                    class="mt-4 w-full rounded-xl bg-theme-surface-variant p-4"
                >
                    <text ref="vision-result" class="text-sm text-theme-on-surface">
                        {{ $visionResult }}
                    </text>
                    <text ref="vision-safety-note" class="mt-3 text-xs text-theme-on-surface-variant">
                        AI output can be wrong. Do not open mains-powered devices; escalate to a qualified professional when unsure.
                    </text>
                </column>
            @endif
        </column>

        <text ref="home-footer" class="pt-8 text-center text-xs text-theme-on-surface-variant">
            Android-only · Local sessions · No account required
        </text>
    </column>
</scroll-view>
