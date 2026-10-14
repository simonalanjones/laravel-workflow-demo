@props([
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $variantClasses = match ($variant) {
        'danger' => 'bg-red-700 text-white hover:bg-red-800 focus-visible:outline-red-600',
        'warning' => 'bg-yellow-700 text-white hover:bg-yellow-800 focus-visible:outline-yellow-600',
        'secondary'
            => 'bg-slate-100 text-slate-900 hover:bg-slate-200 dark:bg-white/10 dark:text-white dark:hover:bg-white/20 focus-visible:outline-indigo-500',
        default => 'bg-indigo-500 text-white hover:bg-indigo-400 focus-visible:outline-indigo-500',
    };
@endphp

<button type="{{ $type }}"
    {{ $attributes->class([
        'cursor-pointer rounded-md px-3 py-2 text-sm font-semibold transition focus-visible:outline-2 focus-visible:outline-offset-2 disabled:opacity-50',
        $variantClasses,
    ]) }}>
    {{ $slot }}
</button>
