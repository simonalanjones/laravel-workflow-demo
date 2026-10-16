@props(['booking'])

<header
    {{ $attributes->class('mb-8') }}>
    <div class="min-w-0 flex-1">
        <h2 class="sr-only">Booking summary</h2>
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex min-w-0 items-center text-sm font-semibold text-slate-900 dark:text-white">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                    class="mr-1.5 size-5 shrink-0 text-slate-400 dark:text-gray-500">
                    <path
                        d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-5.5-2.5a2.5 2.5 0 1 0-5 0 2.5 2.5 0 0 0 5 0ZM10 12a5.99 5.99 0 0 0-4.793 2.39A6.483 6.483 0 0 0 10 16.5a6.483 6.483 0 0 0 4.793-2.11A5.99 5.99 0 0 0 10 12Z"
                        clip-rule="evenodd" fill-rule="evenodd" />
                </svg>
                <span class="sr-only">Customer: </span>
                <span class="break-words">{{ $booking->customer_name }}</span>
            </div>
            <x-bookings.status-badge :status="$booking->status->value" size="medium" />
        </div>
        <div class="mt-3 flex flex-col gap-y-2 sm:flex-row sm:flex-wrap sm:gap-x-6">
            <div class="flex min-w-0 items-center text-sm text-slate-500 dark:text-gray-400">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                    class="mr-1.5 size-5 shrink-0 text-slate-400 dark:text-gray-500">
                    <path d="M3 4a2 2 0 0 0-2 2v.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 6.162V6a2 2 0 0 0-2-2H3Z" />
                    <path
                        d="m19 7.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 7.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7.839Z" />
                </svg>
                <span class="sr-only">Email: </span>
                <span class="break-all">{{ $booking->customer_email }}</span>
            </div>
            <div class="flex items-center text-sm text-slate-500 dark:text-gray-400">
                <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                    class="mr-1.5 size-5 shrink-0 text-slate-400 dark:text-gray-500">
                    <path
                        d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z"
                        clip-rule="evenodd" fill-rule="evenodd" />
                </svg>
                <span>Created <time
                        datetime="{{ $booking->created_at->toIso8601String() }}">{{ $booking->created_at->format('d M Y, H:i') }}</time></span>
            </div>
        </div>
        <p class="mt-2 max-w-full break-all font-mono text-xs leading-5 text-slate-400 dark:text-gray-500">
            <span class="sr-only">Booking reference: </span>{{ $booking->reference }}
        </p>
    </div>
</header>
