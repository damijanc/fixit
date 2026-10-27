<scroll-view
    ref="diagnosis-scroll"
    axis="vertical"
    class="h-full w-full bg-theme-background"
>
    <column
        ref="diagnosis-screen"
        class="safe-area w-full px-6 py-6"
    >
        <text ref="diagnosis-title" font="accent" class="mt-6 text-3xl font-bold text-theme-on-background">
            Diagnosis
        </text>
        <text ref="diagnosis-subtitle" class="mt-1 text-base text-theme-on-surface-variant">
            Photo, symptoms and a cautious next step
        </text>

        <row ref="diagnosis-nav" class="mt-4 w-full gap-3">
            <native:button
                ref="diagnosis-home-button"
                class="flex-1"
                a11y-label="Back to home"
                @tap="goHome"
            >
                Home
            </native:button>
            <native:button
                ref="diagnosis-settings-button"
                class="flex-1"
                a11y-label="Provider settings"
                @tap="openSettings"
            >
                Settings
            </native:button>
        </row>

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

            @if ($safetyNotice !== '')
                <text
                    ref="safety-notice"
                    class="mt-3 w-full rounded-lg border border-theme-outline bg-theme-surface-variant p-3 text-xs font-bold text-theme-on-surface"
                    a11y-label="Safety notice"
                >
                    {{ $safetyNotice }}
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

                <column
                    ref="follow-up-card"
                    class="mt-4 w-full rounded-xl border border-theme-outline p-4"
                >
                    <text ref="follow-up-title" class="text-sm font-bold text-theme-on-surface">
                        Continue the diagnosis
                    </text>
                    <text ref="follow-up-description" class="mt-1 text-xs text-theme-on-surface-variant">
                        Answer the question from the analysis, or add what you observed.
                    </text>

                    <native:outlined-text-input
                        ref="follow-up-input"
                        class="mt-3 w-full"
                        label="Your answer"
                        placeholder="e.g. the light turns on but there is no water"
                        multiline
                        native:model="followUpAnswer"
                        a11y-label="Follow-up answer"
                    />

                    <native:button
                        ref="send-follow-up-button"
                        variant="primary"
                        class="mt-3 w-full"
                        a11y-label="Send follow-up answer"
                        a11y-hint="Continues the diagnosis using earlier observations"
                        @tap="sendFollowUp"
                    >
                        {{ $followUpBusy ? 'Thinking…' : 'Send answer' }}
                    </native:button>

                    @if ($followUpStatus !== '')
                        <text ref="follow-up-status" class="mt-3 text-xs text-theme-on-surface">
                            {{ $followUpStatus }}
                        </text>
                    @endif

                    @if ($followUpResult !== '')
                        <text ref="follow-up-result" class="mt-3 text-sm text-theme-on-surface">
                            {{ $followUpResult }}
                        </text>
                    @endif
                </column>
            @endif
        </column>

        @if ($visionResult !== '')
            <native:button
                ref="open-result-button"
                class="mt-6 w-full"
                a11y-label="Open result screen"
                a11y-hint="Shows the saved diagnosis result"
                @tap="openResult"
            >
                Open result screen
            </native:button>
        @endif

        <text ref="diagnosis-footer" class="pt-8 text-center text-xs text-theme-on-surface-variant">
            Android-only · Local sessions · No account required
        </text>
    </column>
</scroll-view>
