@forelse ($bookings as $booking)
    @php
        $currentStep = $booking->currentStep();
        $hasFailed = $booking->status->value === 'failed' || $booking->workflows->contains('status', 'failed');
        $awaitingSignoff = $booking->workflows->contains(fn ($workflow) => $workflow->type === 'operations_signoff' && $workflow->status === 'awaiting_approval');
    @endphp
    <tr wire:key="booking-{{ $booking->id }}"
        @class([
            'transition-colors',
            'bg-red-50/60 hover:bg-red-50 focus-within:bg-red-50 dark:bg-red-500/5 dark:hover:bg-red-500/10 dark:focus-within:bg-red-500/10' => $hasFailed,
            'hover:bg-slate-50 focus-within:bg-slate-50 dark:hover:bg-white/5 dark:focus-within:bg-white/5' => ! $hasFailed,
        ])>
        <td @class([
            'py-4 pl-7 pr-5 align-top sm:py-5 sm:pl-10 sm:pr-7',
            'shadow-[inset_3px_0_0] shadow-red-500 dark:shadow-red-400' => $hasFailed,
            'shadow-[inset_3px_0_0] shadow-amber-500 dark:shadow-amber-400' => ! $hasFailed && $awaitingSignoff,
        ])>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <span class="text-lg font-semibold tracking-tight tabular-nums text-slate-900 dark:text-white sm:text-xl">£{{ number_format($booking->total(), 2) }}</span>
                <x-bookings.status-badge :status="$booking->status->value" />
            </div>
            <p class="mt-1.5 text-xs leading-5 {{ $hasFailed ? 'font-medium text-red-700 dark:text-red-300' : ($awaitingSignoff ? 'font-medium text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-gray-400') }}">
                <span class="sr-only">Current step: </span>{{ $currentStep }}
            </p>
            <div class="mt-3 min-w-0 sm:hidden">
                <p class="break-words text-sm text-slate-900 dark:text-white">{{ $booking->customer_name }}</p>
                <p title="{{ $booking->reference }}" class="mt-1 max-w-64 truncate text-xs text-slate-500 dark:text-gray-400">{{ $booking->reference }}</p>
            </div>
        </td>
        <td class="hidden px-5 py-5 align-top sm:table-cell">
            <p class="break-words font-medium leading-8 text-slate-900 dark:text-gray-200">{{ $booking->customer_name }}</p>
            <p title="{{ $booking->reference }}" class="mt-1 max-w-64 truncate text-xs leading-5 text-slate-500 dark:text-gray-400">{{ $booking->reference }}</p>
        </td>
        <td class="py-4 pr-5 text-right align-top sm:py-5 sm:pr-7">
            <a href="{{ url('/bookings/' . $booking->id) }}"
                @class([
                    'inline-flex flex-wrap items-center justify-end gap-x-1.5 gap-y-1 rounded-md text-sm underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500',
                    'px-3 py-2 font-semibold text-indigo-700 inset-ring inset-ring-indigo-600/25 hover:bg-indigo-50 dark:text-indigo-300 dark:inset-ring-indigo-400/30 dark:hover:bg-indigo-400/10' => $hasFailed || $awaitingSignoff,
                    'py-2 font-medium text-indigo-600 hover:text-indigo-500 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300' => ! $hasFailed && ! $awaitingSignoff,
                ])>
                <span>{{ $hasFailed || $awaitingSignoff ? 'Review' : 'View' }}<span class="hidden sm:inline"> booking</span></span>
                <span aria-hidden="true">→</span>
                <span class="sr-only">, {{ $booking->reference }}, {{ $booking->customer_name }}</span>
            </a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="3" class="px-5 py-14 text-center">
            <p class="font-medium text-slate-900 dark:text-white">No bookings yet</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">Create a demo booking above to see the workflow in
                action.</p>
        </td>
    </tr>
@endforelse
