@php
    $lines = array_values(array_filter(
        preg_split('/\n+/', $role === 'user' ? trim($text) : \App\Support\DisplayText::plain($text)) ?: [],
        fn (string $line): bool => trim($line) !== '',
    ));
@endphp
@if ($role === 'user')
    <row class="mt-3 w-full justify-end pl-12">
        <column ref="{{ $ref }}" class="gap-2 rounded-2xl rounded-br-md bg-theme-primary px-4 py-3">
            @foreach ($lines as $line)
                <text class="text-sm leading-relaxed text-theme-on-primary">{{ $line }}</text>
            @endforeach
        </column>
    </row>
@else
    <row class="mt-3 w-full items-end gap-2 pr-8">
        <column class="h-7 w-7 items-center justify-center rounded-full bg-theme-primary-container">
            <native:icon name="handyman" size="16" color="#0F766E" dark-color="#5EEAD4" />
        </column>
        <column ref="{{ $ref }}" class="flex-shrink gap-2 rounded-2xl rounded-bl-md bg-theme-surface px-4 py-3">
            @foreach ($lines as $line)
                <text class="text-sm leading-relaxed text-theme-on-surface">{{ $line }}</text>
            @endforeach
        </column>
    </row>
@endif
