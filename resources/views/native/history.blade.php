<scroll-view
    ref="history-scroll"
    axis="vertical"
    class="h-full w-full bg-theme-background"
>
    <column
        ref="history-screen"
        class="safe-area w-full px-6 py-6"
    >
        <text ref="history-title" font="accent" class="mt-6 text-3xl font-bold text-theme-on-background">
            History
        </text>
        <text ref="history-subtitle" class="mt-1 text-base text-theme-on-surface-variant">
            Saved repair sessions on this device
        </text>

        @if ($sessions === [])
            <text ref="history-empty" class="mt-8 text-sm text-theme-on-surface-variant">
                No repair sessions yet. Start a diagnosis from Home.
            </text>
        @else
            @foreach ($sessions as $session)
                <native:button
                    ref="history-session-{{ $session['id'] }}"
                    class="mt-4 w-full"
                    a11y-label="Open saved diagnosis"
                    @tap="openSession('{{ $session['id'] }}')"
                >
                    {{ $session['summary'] }} — {{ $session['label'] }}
                </native:button>
            @endforeach
        @endif

        <native:button
            ref="history-home-button"
            class="mt-8 w-full"
            a11y-label="Back to home"
            @tap="goHome"
        >
            Back to home
        </native:button>
    </column>
</scroll-view>
