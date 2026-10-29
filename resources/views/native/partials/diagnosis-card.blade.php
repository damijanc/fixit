{{-- A structured diagnosis (DiagnosisReply) in the assistant's place in the thread. --}}
<row class="mt-3 w-full items-end gap-2 pr-8">
    <column class="h-7 w-7 items-center justify-center rounded-full bg-theme-primary-container">
        <native:icon name="handyman" size="16" color="#0F766E" dark-color="#5EEAD4" />
    </column>
    <column ref="{{ $ref }}-card" class="flex-shrink gap-3 rounded-2xl rounded-bl-md bg-theme-surface px-4 py-3">
        <column class="gap-1">
            <text class="text-xs font-bold text-theme-on-surface-variant">{{ $reply->object }}</text>
            <text class="text-sm leading-relaxed text-theme-on-surface">{{ $reply->likelyCause }}</text>
            <text class="text-xs text-theme-on-surface-variant">{{ ucfirst($reply->confidence) }} confidence</text>
        </column>

        @if ($reply->safeChecks !== [])
            <column class="gap-2">
                <text class="text-xs font-bold text-theme-on-surface-variant">Safe checks</text>
                @foreach ($reply->safeChecks as $index => $check)
                    <row class="w-full gap-2">
                        <text class="text-sm font-bold text-theme-primary">{{ $index + 1 }}.</text>
                        <text class="flex-1 text-sm leading-relaxed text-theme-on-surface">{{ $check }}</text>
                    </row>
                @endforeach
            </column>
        @endif

        @if ($reply->needsProfessional)
            <row ref="{{ $ref }}-professional" class="w-full gap-2 rounded-xl bg-theme-warning-container p-3">
                <native:icon name="warning" size="18" color="#B45309" dark-color="#FCD34D" />
                <text class="flex-1 text-sm text-theme-on-warning-container">
                    Use a qualified professional for anything beyond these checks.
                </text>
            </row>
        @endif

        @if ($reply->question !== '')
            <row class="w-full gap-2">
                <native:icon name="help" size="18" color="#0F766E" dark-color="#5EEAD4" />
                <text class="flex-1 text-sm leading-relaxed text-theme-on-surface">{{ $reply->question }}</text>
            </row>
        @endif
    </column>
</row>
