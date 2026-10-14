@props(['status'])

@php
    $tone = match ($status) {
        'processed'
            => 'bg-green-50 dark:bg-green-500/10 text-green-700 dark:text-green-300 inset-ring-green-600/20 dark:inset-ring-green-400/20',
        'failed'
            => 'bg-red-50 dark:bg-red-500/10 text-red-700 dark:text-red-300 inset-ring-red-600/20 dark:inset-ring-red-400/20',
        'processing'
            => 'bg-blue-50 dark:bg-blue-500/10 text-indigo-700 dark:text-indigo-300 inset-ring-blue-600/20 dark:inset-ring-blue-400/20',
        default
            => 'bg-slate-50 dark:bg-white/5 text-slate-700 dark:text-gray-300 inset-ring-slate-500/10 dark:inset-ring-white/10',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-md px-2 py-1 text-xs font-medium inset-ring', $tone]) }}>
    {{ ucfirst($status) }}
</span>
