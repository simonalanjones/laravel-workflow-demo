<svg class="{{ $iconClass ?? 'size-4' }} shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
    @switch($icon)
        @case('check')
            <path d="m6 12 4 4 8-8" />
            @break
        @case('arrow')
            <path d="M19 12H5m6-6-6 6 6 6" />
            @break
    @endswitch
</svg>
