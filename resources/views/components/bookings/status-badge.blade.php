@props([
    'status',
    'size' => 'small',
    'dot' => false,
])

@php
    $tone = match ($status) {
        'processed'
            => 'bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-400 inset-ring-green-600/20 dark:inset-ring-green-500/20',
        'failed'
            => 'bg-red-50 dark:bg-red-900/30 text-red-700 dark:text-red-400 inset-ring-red-600/20 dark:inset-ring-red-500/20',
        'processing'
            => 'bg-blue-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-400 inset-ring-blue-600/20 dark:inset-ring-indigo-500/20',
        default
            => 'bg-slate-50 dark:bg-gray-700/50 text-slate-700 dark:text-gray-400 inset-ring-slate-500/10 dark:inset-ring-gray-500/20',
    };

    $sizing = match ($size) {
        'small' => 'gap-1.5 text-xs font-medium px-2 py-1',
        'medium' => 'gap-2 text-sm px-3 py-1.5',
    };

    $dotSizing = match ($size) {
        'small' => 'size-1.5',
        'medium' => 'size-2',
    };
@endphp

<span {{ $attributes->class([
    'inline-flex items-center rounded-md inset-ring', $tone, $sizing
    ]) }}>
    @if ($dot)
        <span aria-hidden="true" class="shrink-0 rounded-full bg-current {{ $dotSizing }}"></span>
    @endif
    {{ ucfirst($status) }}
</span>
