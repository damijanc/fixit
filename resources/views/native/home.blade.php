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

        <text ref="home-intro" class="mt-4 text-sm text-theme-on-surface-variant">
            Start a diagnosis with a photo and symptoms. Everything stays on this device until you choose to send it.
        </text>

        <native:button
            ref="start-diagnosis-button"
            variant="primary"
            class="mt-6 w-full"
            a11y-label="Start a new diagnosis"
            @tap="startDiagnosis"
        >
            Start a new diagnosis
        </native:button>

        @if ($latestSessionId !== null)
            <column
                ref="latest-session-card"
                class="mt-5 w-full rounded-2xl bg-theme-surface p-5 shadow-md"
            >
                <text ref="latest-title" class="text-sm font-bold text-theme-on-surface">
                    Last diagnosis · {{ $latestUpdated }}
                </text>
                <text ref="latest-status" class="mt-1 text-xs text-theme-on-surface-variant">
                    Status: {{ $latestStatus }}
                </text>
                @if ($latestSummary !== '')
                    <text ref="latest-summary" class="mt-2 text-sm text-theme-on-surface">
                        {{ $latestSummary }}
                    </text>
                @endif
                <native:button
                    ref="resume-last-button"
                    class="mt-4 w-full"
                    a11y-label="Continue last diagnosis"
                    @tap="resumeLast"
                >
                    Continue last diagnosis
                </native:button>
            </column>
        @endif

        <native:button
            ref="open-history-button"
            class="mt-4 w-full"
            a11y-label="Diagnosis history"
            @tap="openHistory"
        >
            History ({{ $sessionCount }})
        </native:button>

        <native:button
            ref="home-settings-button"
            class="mt-4 w-full"
            a11y-label="Provider settings"
            @tap="openSettings"
        >
            Provider settings
        </native:button>

        <text ref="home-footer" class="pt-8 text-center text-xs text-theme-on-surface-variant">
            Android-only · Local sessions · No account required
        </text>
    </column>
</scroll-view>
