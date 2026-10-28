<column
    ref="result-screen"
    class="safe-area h-full w-full bg-theme-background"
>
    <row ref="result-top-bar" class="w-full items-center gap-1 px-2 py-1">
        <native:button
            ref="result-home-button"
            variant="ghost"
            icon="arrow_back"
            a11y-label="Back to home"
            @tap="goHome"
        />
        <column class="flex-1">
            <text ref="result-title" class="text-lg font-bold text-theme-on-background">
                Result
            </text>
            @if ($message === '')
                <text ref="result-status" class="text-xs text-theme-on-surface-variant">
                    {{ \App\Support\DisplayText::status($status) }} · saved on this phone
                </text>
            @endif
        </column>
        <native:button
            ref="result-history-button"
            variant="ghost"
            icon="history"
            a11y-label="Open history"
            @tap="goHistory"
        />
    </row>

    <scroll-view
        ref="result-scroll"
        axis="vertical"
        class="w-full flex-1"
    >
        <column ref="result-thread" class="w-full px-4 pb-6">
            @if ($message !== '')
                <column class="mt-16 w-full items-center px-6">
                    <native:icon name="search_off" size="48" color="#5F625F" dark-color="#A1A09A" />
                    <text ref="result-message" class="mt-4 text-center text-base text-theme-on-background">
                        {{ $message }}
                    </text>
                </column>
            @else
                <row class="mt-4 w-full justify-end pl-12">
                    <column class="rounded-2xl rounded-br-sm bg-theme-primary p-1">
                        @if ($photoPath !== '')
                            <image
                                ref="result-photo"
                                src="{{ $photoPath }}"
                                alt="Repair session photo"
                                class="h-44 w-56 rounded-xl object-cover"
                            />
                        @endif
                        @if ($symptoms !== '')
                            <text ref="result-symptoms" class="max-w-56 px-3 py-2 text-sm text-theme-on-primary">
                                {{ $symptoms }}
                            </text>
                        @endif
                    </column>
                </row>

                @include('native.partials.message', ['role' => 'assistant', 'text' => $summary, 'ref' => 'result-summary'])

                @foreach ($turns as $index => $turn)
                    @include('native.partials.message', ['role' => $turn['role'], 'text' => $turn['text'], 'ref' => 'result-turn-'.$index])
                @endforeach

                @if ($safetyNotice !== '')
                    <row
                        ref="result-safety"
                        class="mt-4 w-full gap-3 rounded-2xl bg-theme-warning-container p-4"
                        a11y-label="Safety notice"
                    >
                        <native:icon name="warning" size="20" color="#B45309" dark-color="#FCD34D" />
                        <text class="flex-1 text-sm font-bold text-theme-on-warning-container">
                            {{ $safetyNotice }}
                        </text>
                    </row>
                @endif
            @endif
        </column>
    </scroll-view>

    @if ($message === '')
        <column class="w-full border-t border-theme-outline bg-theme-surface px-4 py-3">
            <native:button
                ref="result-continue-button"
                variant="primary"
                icon="chat"
                class="w-full"
                a11y-label="Continue this diagnosis"
                @tap="continueDiagnosis"
            >
                Continue this diagnosis
            </native:button>
        </column>
    @endif
</column>
