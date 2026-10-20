@props(['booking'])

<header {{ $attributes->class('mb-6 flex flex-col items-start gap-3 sm:flex-row sm:items-center sm:gap-4') }}>
    <h2 class="sr-only">Booking summary</h2>
    <x-bookings.status-badge :status="$booking->status->value" size="medium" />
    <p class="min-w-0 max-w-full break-all font-mono text-xs leading-5 text-slate-400 dark:text-gray-500">
        <span class="sr-only">Booking reference: </span>{{ $booking->reference }}
    </p>
</header>
