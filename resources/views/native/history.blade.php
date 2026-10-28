<scroll-view
    ref="history-scroll"
    axis="vertical"
    class="h-full w-full bg-theme-background"
>
    <column
        ref="history-screen"
        class="safe-area w-full px-5 pb-8"
    >
        <row ref="history-top-bar" class="mt-2 w-full items-center gap-1">
            <native:button
                ref="history-home-button"
                variant="ghost"
                icon="arrow_back"
                a11y-label="Back to home"
                @tap="goHome"
            />
            <text ref="history-title" class="flex-1 text-xl font-bold text-theme-on-background">
                History
            </text>
        </row>

        @if ($sessions === [])
            <column ref="history-empty" class="mt-16 w-full items-center px-6">
                <native:icon name="history" size="48" color="#0F766E" dark-color="#5EEAD4" />
                <text class="mt-4 text-center text-base font-bold text-theme-on-background">
                    No repair sessions yet.
                </text>
                <text class="mt-1 text-center text-sm text-theme-on-surface-variant">
                    Start a diagnosis from Home and it will be saved here on this phone.
                </text>
            </column>
        @else
            <text ref="history-subtitle" class="mt-2 text-sm text-theme-on-surface-variant">
                Saved on this phone
            </text>

            <column ref="history-list" class="mt-3 w-full rounded-2xl bg-theme-surface">
                @foreach ($sessions as $session)
                    <native:list-item
                        ref="history-session-{{ $session['id'] }}"
                        overline="{{ $session['label'] }}"
                        headline="{{ $session['title'] }}"
                        supporting="{{ $session['summary'] }}"
                        leadingIcon="{{ $session['escalated'] ? 'warning' : 'build' }}"
                        trailingIcon="chevron_right"
                        a11y-label="Open saved diagnosis"
                        @tap="openSession('{{ $session['id'] }}')"
                    />
                    @if (! $loop->last)
                        <native:divider class="mx-4" />
                    @endif
                @endforeach
            </column>
        @endif
    </column>
</scroll-view>
