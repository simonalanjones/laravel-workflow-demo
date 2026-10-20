@forelse ($bookings as $booking)
    @php
        $currentStep = $booking->currentStep();
        $referencePreview = preg_replace('/^(.+?-[a-f0-9]{8})-[a-f0-9]{4}-[a-f0-9-]+$/i', '$1', $booking->reference);
        $hasFailed = $booking->status->value === 'failed' || $booking->workflowSteps->contains('status', 'failed');
        $awaitingSignoff = $booking->workflowSteps->contains(
            fn($workflowStep) => $workflowStep->type === 'operations_signoff' &&
                $workflowStep->status === 'awaiting_approval',
        );
    @endphp
    <tr wire:key="booking-{{ $booking->id }}" @class([
        'transition-colors',
        'bg-red-50/60 hover:bg-red-50 focus-within:bg-red-50 dark:bg-red-500/5 dark:hover:bg-red-500/10 dark:focus-within:bg-red-500/10' => $hasFailed,
        'hover:bg-slate-50 focus-within:bg-slate-50 dark:hover:bg-white/5 dark:focus-within:bg-white/5' => !$hasFailed,
    ])>
        <td @class([
            'py-3 pl-7 pr-5 align-middle sm:py-4 sm:pl-10 sm:pr-7',
            'shadow-[inset_3px_0_0] shadow-red-500 dark:shadow-red-400' => $hasFailed,
            'shadow-[inset_3px_0_0] shadow-amber-500 dark:shadow-amber-400' =>
                !$hasFailed && $awaitingSignoff,
        ])>
            <p class="break-words text-base font-semibold text-slate-900 dark:text-white">{{ $booking->customer_name }}
            </p>
            <p title="{{ $booking->reference }}"
                class="mt-1 break-all font-mono text-xs text-slate-500 dark:text-gray-400"
            >{{ $referencePreview }}</p>
            <p
                class="{{ $hasFailed ? 'font-medium text-red-700 dark:text-red-300' : ($awaitingSignoff ? 'font-medium text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-gray-400') }} mt-1.5 text-xs leading-5 sm:hidden">
                <span class="sr-only">Current step: </span>{{ $currentStep }}
            </p>
        </td>
        <td class="hidden px-5 py-4 align-middle sm:table-cell">
            <p
                class="{{ $hasFailed ? 'font-medium text-red-700 dark:text-red-300' : ($awaitingSignoff ? 'font-medium text-amber-700 dark:text-amber-300' : 'text-slate-500 dark:text-gray-400') }} text-sm leading-6">
                <span class="sr-only">Current step: </span>{{ $currentStep }}
            </p>
        </td>
        <td class="hidden px-5 py-4 text-right align-middle sm:table-cell">
            <span
                class="whitespace-nowrap text-sm tabular-nums text-slate-600 dark:text-gray-300">£{{ number_format($booking->total(), 2) }}</span>
        </td>
        <td class="py-3 pr-5 text-right align-middle sm:py-4 sm:pr-7">
            <p class="mb-1 text-sm tabular-nums text-slate-600 sm:hidden dark:text-gray-300">
                £{{ number_format($booking->total(), 2) }}</p>
            <a href="{{ route('bookings.show', ['booking' => $booking->public_id]) }}" @class([
                'inline-flex flex-wrap items-center justify-end gap-x-1.5 gap-y-1 rounded-md text-sm underline-offset-4 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500',
                'px-3 py-2 font-semibold text-indigo-700 inset-ring inset-ring-indigo-600/25 hover:bg-indigo-50 dark:text-indigo-300 dark:inset-ring-indigo-400/30 dark:hover:bg-indigo-400/10' =>
                    $hasFailed || $awaitingSignoff,
                'py-2 font-medium text-indigo-600 hover:text-indigo-500 hover:underline dark:text-indigo-400 dark:hover:text-indigo-300' =>
                    !$hasFailed && !$awaitingSignoff,
            ])>
                <span>{{ $hasFailed || $awaitingSignoff ? 'Review' : 'View' }}<span class="hidden sm:inline">
                        booking</span></span>
                <span aria-hidden="true">→</span>
                <span class="sr-only">, {{ $booking->reference }}, {{ $booking->customer_name }}</span>
            </a>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="px-5 py-14 text-center">
            <svg
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                aria-hidden="true"
                class="mx-auto size-12 text-slate-400 dark:text-gray-500"
            >
                <path
                    d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"
                    stroke-width="2"
                    vector-effect="non-scaling-stroke"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                />
            </svg>
            <h3 class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">No bookings yet</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-gray-400">Get started by creating a new booking.</p>
            <div class="mt-6">
                <a href="{{ route('bookings.create') }}"
                    class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400"
                >
                    <svg
                        viewBox="0 0 20 20"
                        fill="currentColor"
                        aria-hidden="true"
                        class="-ml-0.5 size-5"
                    >
                        <path
                            d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z"
                        />
                    </svg>
                    New booking
                </a>
            </div>
        </td>
    </tr>
@endforelse
