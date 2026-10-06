@props(['status'])

@php
    $tone = match ($status) {
        'processed' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/10',
        'failed' => 'bg-red-50 text-red-700 ring-red-600/10',
        'processing' => 'bg-blue-50 text-blue-700 ring-blue-600/10',
        default => 'bg-slate-100 text-slate-600 ring-slate-500/10',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset', $tone]) }}>
    <span aria-hidden="true" class="size-1.5 rounded-full bg-current"></span>
    {{ ucfirst($status) }}
</span>
