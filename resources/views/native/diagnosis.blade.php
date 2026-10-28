<column
    ref="diagnosis-screen"
    class="safe-area h-full w-full bg-theme-background"
>
    <row ref="diagnosis-nav" class="w-full items-center gap-1 px-2 py-1">
        <native:button
            ref="diagnosis-home-button"
            variant="ghost"
            icon="arrow_back"
            a11y-label="Back to home"
            @tap="goHome"
        />
        <column class="flex-1">
            <text ref="diagnosis-title" class="text-lg font-bold text-theme-on-background">
                {{ $visionResult !== '' ? \App\Support\DisplayText::repairTitle($symptoms, $visionResult) : 'Diagnosis' }}
            </text>
            @if ($visionResult === '')
                <text ref="diagnosis-subtitle" class="text-xs text-theme-on-surface-variant">
                    Photo, symptoms and a cautious next step
                </text>
            @endif
        </column>
        @if ($visionResult !== '')
            <native:button
                ref="open-result-button"
                variant="ghost"
                icon="description"
                a11y-label="Open result screen"
                a11y-hint="Shows the saved diagnosis result"
                @tap="openResult"
            />
        @endif
        <native:button
            ref="diagnosis-settings-button"
            variant="ghost"
            icon="settings"
            a11y-label="Provider settings"
            @tap="openSettings"
        />
    </row>

    <scroll-view
        ref="diagnosis-scroll"
        axis="vertical"
        class="w-full flex-1"
        scroll-anchor="{{ $visionResult !== '' ? 'bottom' : 'top' }}"
    >
        <column ref="diagnosis-thread" class="w-full px-4 pb-6">
            @if ($photoPath === '')
                <column
                    ref="camera-card"
                    class="mt-4 w-full items-center rounded-3xl bg-theme-surface p-6"
                >
                    <column class="h-20 w-20 items-center justify-center rounded-full bg-theme-primary-container">
                        <native:icon name="add_a_photo" size="36" color="#0F766E" dark-color="#5EEAD4" />
                    </column>
                    <text ref="camera-title" class="mt-4 text-center text-xl font-bold text-theme-on-surface">
                        Show FixIt the problem
                    </text>
                    <text ref="camera-description" class="mt-2 text-center text-sm text-theme-on-surface-variant">
                        Take a clear photo of the appliance or the faulty part. It stays on this phone until you choose to send it.
                    </text>

                    <native:button
                        ref="take-photo-button"
                        variant="primary"
                        icon="photo_camera"
                        class="mt-6 w-full"
                        a11y-label="Take a photo"
                        a11y-hint="Opens the camera to capture a repair photo"
                        @tap="takePhoto"
                    >
                        Take a photo
                    </native:button>

                    <row ref="camera-hint" class="mt-4 w-full gap-2">
                        <native:icon name="info" size="16" color="#5F625F" dark-color="#A1A09A" />
                        <text class="flex-1 text-xs text-theme-on-surface-variant">
                            In the camera app, take the photo and tap the ✓ checkmark to confirm. FixIt only receives the photo after that.
                        </text>
                    </row>
                </column>
            @elseif ($visionResult === '')
                <column
                    ref="camera-card"
                    class="mt-4 w-full rounded-3xl bg-theme-surface p-4"
                >
                    <image
                        ref="captured-photo"
                        src="{{ $photoPath }}"
                        alt="Captured repair photo"
                        class="h-56 w-full rounded-2xl object-cover"
                    />

                    <native:outlined-text-input
                        ref="symptoms-input"
                        class="mt-4 w-full"
                        label="What's wrong?"
                        placeholder="No water comes out, the pump hums"
                        multiline
                        native:model="symptoms"
                        a11y-label="Symptoms"
                    />

                    <row ref="upload-consent" class="mt-4 w-full gap-2">
                        <native:icon name="shield" size="16" color="#5F625F" dark-color="#A1A09A" />
                        <text class="flex-1 text-xs text-theme-on-surface-variant">
                            Sending uploads this photo and symptoms to {{ $providerName }} for analysis. Do not send photos containing people, documents or personal information.
                        </text>
                    </row>

                    @if ($visionError !== '')
                        <row
                            ref="vision-error"
                            class="mt-4 w-full gap-3 rounded-2xl bg-theme-warning-container p-3"
                            a11y-label="Photo not sent"
                        >
                            <native:icon name="error" size="20" color="#B45309" dark-color="#FCD34D" />
                            <text class="flex-1 text-sm text-theme-on-warning-container">
                                {{ $visionError }}
                            </text>
                        </row>
                    @endif

                    <native:button
                        ref="analyze-photo-button"
                        variant="primary"
                        icon="send"
                        class="mt-4 w-full"
                        :loading="$visionBusy"
                        a11y-label="Send photo to {{ $providerName }}"
                        a11y-hint="Uploads the photo to {{ $providerName }} for a cautious diagnosis"
                        @tap="analyzePhoto"
                    >
                        {{ $visionBusy ? 'Sending to '.$providerName.'…' : 'Send photo to '.$providerName }}
                    </native:button>

                    <native:button
                        ref="discard-photo-button"
                        variant="ghost"
                        icon="delete"
                        class="mt-1 w-full"
                        a11y-label="Discard photo"
                        a11y-hint="Deletes the captured photo from this device"
                        @tap="discardPhoto"
                    >
                        Discard photo
                    </native:button>
                </column>
            @else
                <row class="mt-4 w-full justify-end pl-12">
                    <column class="rounded-2xl rounded-br-sm bg-theme-primary p-1">
                        <image
                            ref="captured-photo"
                            src="{{ $photoPath }}"
                            alt="Captured repair photo"
                            class="h-44 w-56 rounded-xl object-cover"
                        />
                        @if ($symptoms !== '')
                            <text ref="thread-symptoms" class="max-w-56 px-3 py-2 text-sm text-theme-on-primary">
                                {{ $symptoms }}
                            </text>
                        @endif
                    </column>
                </row>

                @include('native.partials.message', ['role' => 'assistant', 'text' => $visionResult, 'ref' => 'vision-result'])

                @foreach ($turns as $index => $turn)
                    @include('native.partials.message', ['role' => $turn['role'], 'text' => $turn['text'], 'ref' => 'turn-'.$index])
                @endforeach

                @if ($followUpBusy)
                    <row ref="thinking-indicator" class="mt-3 w-full items-center gap-2 pl-9">
                        <native:activity-indicator size="sm" />
                        <text class="text-xs text-theme-on-surface-variant">Thinking…</text>
                    </row>
                @endif
            @endif

            @if ($safetyNotice !== '')
                <row
                    ref="safety-notice"
                    class="mt-4 w-full gap-3 rounded-2xl bg-theme-warning-container p-4"
                    a11y-label="Safety notice"
                >
                    <native:icon name="warning" size="20" color="#B45309" dark-color="#FCD34D" />
                    <text class="flex-1 text-sm font-bold text-theme-on-warning-container">
                        {{ $safetyNotice }}
                    </text>
                </row>
            @endif

            @if ($visionResult !== '')
                <text ref="vision-safety-note" class="mt-4 px-2 text-center text-xs text-theme-on-surface-variant">
                    AI output can be wrong. Do not open mains-powered devices; escalate to a qualified professional when unsure.
                </text>
            @endif

            {{-- One status line: the most recent step's message wins. --}}
            @php
                $statusLine = collect([
                    'follow-up-status' => $followUpStatus,
                    'vision-status' => $visionStatus,
                    'camera-status' => $cameraStatus,
                    'session-notice' => $sessionNotice,
                ])->filter(fn (string $text): bool => $text !== '');
            @endphp
            @if ($statusLine->isNotEmpty())
                <text
                    ref="{{ $statusLine->keys()->first() }}"
                    class="mt-2 px-2 text-center text-xs text-theme-on-surface-variant"
                >
                    {{ $statusLine->first() }}
                </text>
            @endif
        </column>
    </scroll-view>

    @if ($visionResult !== '')
        <row
            ref="follow-up-card"
            class="w-full items-end gap-2 border-t border-theme-outline bg-theme-surface px-3 py-2"
        >
            <native:outlined-text-input
                ref="follow-up-input"
                class="flex-1"
                label="Your answer"
                placeholder="The light turns on but there is no water"
                multiline
                native:model="followUpAnswer"
                a11y-label="Follow-up answer"
            />
            <native:button
                ref="send-follow-up-button"
                variant="primary"
                icon="send"
                class="mb-1"
                :loading="$followUpBusy"
                a11y-label="Send follow-up answer"
                a11y-hint="Continues the diagnosis using earlier observations"
                @tap="sendFollowUp"
            />
        </row>
    @endif
</column>
