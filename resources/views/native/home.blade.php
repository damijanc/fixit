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
    </column>

    <text ref="home-footer" class="mt-auto pt-8 text-center text-xs text-theme-on-surface-variant">
        Android-only · Local sessions · No account required
    </text>
</column>
