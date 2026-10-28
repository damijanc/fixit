<scroll-view
    ref="home-scroll"
    axis="vertical"
    class="h-full w-full bg-theme-background"
>
    <column
        ref="home-screen"
        class="safe-area w-full px-5 pb-8"
    >
        <row ref="home-top-bar" class="mt-2 w-full items-center">
            <text ref="app-title" font="accent" class="flex-1 text-3xl text-theme-on-background">
                FixIt
            </text>
            <native:button
                ref="home-settings-button"
                variant="ghost"
                icon="settings"
                a11y-label="Provider settings"
                @tap="openSettings"
            />
        </row>

        <column
            ref="home-hero"
            class="mt-4 w-full rounded-3xl bg-theme-primary-container p-6"
        >
            <native:icon name="photo_camera" size="36" color="#0F766E" dark-color="#5EEAD4" />
            <text ref="app-subtitle" class="mt-3 text-2xl font-bold text-theme-on-primary-container">
                What needs fixing?
            </text>
            <text ref="home-intro" class="mt-2 text-sm leading-relaxed text-theme-on-primary-container">
                Take a photo of the appliance, describe what's wrong and troubleshoot it step by step with AI.
            </text>
            <native:button
                ref="start-diagnosis-button"
                variant="primary"
                icon="photo_camera"
                class="mt-5 w-full"
                a11y-label="Start a new diagnosis"
                @tap="startDiagnosis"
            >
                Start a new diagnosis
            </native:button>
        </column>

        <row ref="recent-header" class="mt-8 w-full items-center">
            <text class="flex-1 text-base font-bold text-theme-on-background">
                Recent repairs
            </text>
            <native:button
                ref="open-history-button"
                variant="ghost"
                a11y-label="Diagnosis history"
                @tap="openHistory"
            >
                History ({{ $sessionCount }})
            </native:button>
        </row>

        @if ($latestSessionId !== null)
            <column
                ref="latest-session-card"
                class="mt-2 w-full rounded-2xl bg-theme-surface"
            >
                <native:list-item
                    ref="resume-last-button"
                    overline="Last diagnosis · {{ $latestUpdated }}"
                    headline="{{ $latestTitle }}"
                    supporting="{{ $latestStatus }}"
                    leadingIcon="build"
                    trailingIcon="chevron_right"
                    a11y-label="Continue last diagnosis"
                    @tap="resumeLast"
                />
                @if ($latestSummary !== '')
                    <text ref="latest-summary" class="px-4 pb-4 text-sm text-theme-on-surface-variant">
                        {{ $latestSummary }}
                    </text>
                @endif
            </column>
        @else
            <row
                ref="home-empty"
                class="mt-2 w-full items-center gap-3 rounded-2xl border border-theme-outline p-4"
            >
                <native:icon name="history" size="22" color="#5F625F" dark-color="#A1A09A" />
                <text class="flex-1 text-sm text-theme-on-surface-variant">
                    Your repairs show up here, so you can pick up where you left off.
                </text>
            </row>
        @endif

        <row ref="home-footer" class="mt-10 w-full items-center justify-center gap-2">
            <native:icon name="lock" size="14" color="#5F625F" dark-color="#A1A09A" />
            <text class="text-xs text-theme-on-surface-variant">
                Stored only on this phone · No account needed
            </text>
        </row>
    </column>
</scroll-view>
