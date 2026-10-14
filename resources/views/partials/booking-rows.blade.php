@forelse ($bookings as $booking)
    <tr wire:key="booking-{{ $booking->id }}"
        class="transition hover:bg-slate-50 dark:hover:bg-white/5 focus-within:bg-slate-50 dark:focus-within:bg-white/5">
        <td class="px-5 py-4 sm:px-7">
            <a href="{{ url('/bookings/' . $booking->id) }}"
                class="break-words rounded-sm font-medium text-slate-900 dark:text-white underline-offset-4 hover:text-indigo-700 dark:hover:text-indigo-300 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500">{{ $booking->reference }}</a>
            <p class="mt-1.5 text-xs leading-5 text-slate-500 dark:text-gray-400 md:hidden">{{ $booking->customer_name }}
            </p>
            <x-bookings.status-badge :status="$booking->status->value" class="mt-2 sm:hidden" />
        </td>
        <td class="hidden px-5 py-4 text-slate-500 dark:text-gray-400 md:table-cell">{{ $booking->customer_name }}</td>
        <td class="hidden px-5 py-4 sm:table-cell">
            <x-bookings.status-badge :status="$booking->status->value" />
        </td>
        <td class="px-5 py-4 text-right font-medium text-slate-500 dark:text-gray-400 tabular-nums sm:px-7">
            £{{ number_format($booking->total(), 2) }}</td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="px-5 py-14 text-center">
            <p class="font-medium text-slate-900 dark:text-white">No bookings yet</p>
            <p class="mt-2 text-sm text-slate-500 dark:text-gray-400">Create a demo booking above to see the workflow in
                action.</p>
        </td>
    </tr>
@endforelse
