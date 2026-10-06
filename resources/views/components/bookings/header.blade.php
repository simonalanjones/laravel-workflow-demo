@props(['booking'])

<header {{ $attributes->class('mb-8 flex flex-col justify-between gap-6 border-b border-slate-200/80 pb-8 sm:flex-row sm:items-end') }}>
    <div class="min-w-0">
        <div class="mb-3 flex flex-wrap items-center gap-3">
            <p class="text-xs font-medium tracking-wide text-slate-500">Booking details</p>
            <x-bookings.status-badge :status="$booking->status->value" />
        </div>
        <h1 class="break-words text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">{{ $booking->reference }}</h1>
        <div class="mt-3 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">
            <span class="font-medium text-slate-700">{{ $booking->customer_name }}</span>
            <span aria-hidden="true" class="hidden text-slate-300 sm:inline">/</span>
            <span class="break-all text-slate-500">{{ $booking->customer_email }}</span>
        </div>
    </div>
    <dl class="flex shrink-0 gap-7 text-sm sm:text-right">
        <div><dt class="mb-1 text-xs text-slate-500">Created</dt><dd class="text-slate-600">{{ $booking->created_at->format('d M Y, H:i') }}</dd></div>
        <div><dt class="mb-1 text-xs text-slate-500">Booking ID</dt><dd class="text-slate-600">#{{ $booking->id }}</dd></div>
    </dl>
</header>
