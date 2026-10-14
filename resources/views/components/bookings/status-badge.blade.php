@props(['status'])

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
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-md px-2 py-1 text-xs font-medium inset-ring', $tone]) }}>
    {{ ucfirst($status) }}
</span>
