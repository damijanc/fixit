<scroll-view
    ref="result-scroll"
    axis="vertical"
    class="h-full w-full bg-theme-background"
>
    <column
        ref="result-screen"
        class="safe-area w-full px-6 py-6"
    >
        <text ref="result-title" font="accent" class="mt-6 text-3xl font-bold text-theme-on-background">
            Result
        </text>
        <text ref="result-subtitle" class="mt-1 text-base text-theme-on-surface-variant">
            Saved diagnosis from this device
        </text>

        @if ($message !== '')
            <text ref="result-message" class="mt-8 text-sm text-theme-on-surface">
                {{ $message }}
            </text>
        @else
            <text ref="result-status" class="mt-4 text-xs text-theme-on-surface-variant">
                Status: {{ $status }}
            </text>

            @if ($photoPath !== '')
                <image
                    ref="result-photo"
                    src="{{ $photoPath }}"
                    alt="Repair session photo"
                    class="mt-4 h-48 w-full rounded-xl object-cover"
                />
            @endif

            @if ($symptoms !== '')
                <text ref="result-symptoms" class="mt-4 text-sm text-theme-on-surface">
                    Symptoms: {{ $symptoms }}
                </text>
            @endif

            <column
                ref="result-card"
                class="mt-5 w-full rounded-2xl bg-theme-surface p-5 shadow-md"
            >
                <text ref="result-summary" class="text-sm text-theme-on-surface">
                    {{ $summary }}
                </text>
            </column>

            @if ($safetyNotice !== '')
                <text ref="result-safety" class="mt-3 w-full rounded-lg border border-theme-outline bg-theme-surface-variant p-3 text-xs font-bold text-theme-on-surface">
                    {{ $safetyNotice }}
                </text>
            @endif

            @if ($followUp !== '')
                <column
                    ref="result-follow-up-card"
                    class="mt-4 w-full rounded-xl border border-theme-outline p-4"
                >
                    <text ref="result-follow-up-label" class="text-xs font-bold text-theme-on-surface-variant">
                        Follow-up
                    </text>
                    <text ref="result-follow-up" class="mt-2 text-sm text-theme-on-surface">
                        {{ $followUp }}
                    </text>
                </column>
            @endif

            <native:button
                ref="result-continue-button"
                variant="primary"
                class="mt-6 w-full"
                a11y-label="Continue this diagnosis"
                @tap="continueDiagnosis"
            >
                Continue this diagnosis
            </native:button>
        @endif

        <native:button
            ref="result-history-button"
            class="mt-4 w-full"
            a11y-label="Open history"
            @tap="goHistory"
        >
            History
        </native:button>

        <native:button
            ref="result-home-button"
            class="mt-4 w-full"
            a11y-label="Back to home"
            @tap="goHome"
        >
            Back to home
        </native:button>
    </column>
</scroll-view>
