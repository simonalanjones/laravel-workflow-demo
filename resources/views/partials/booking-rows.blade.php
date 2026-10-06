@forelse ($bookings as $booking)
    <tr wire:key="booking-{{ $booking->id }}" class="transition hover:bg-slate-50/70 focus-within:bg-slate-50/70">
        <td class="px-5 py-5 sm:px-7">
            <a href="{{ url('/bookings/' . $booking->id) }}" class="break-words rounded-sm font-medium text-slate-800 underline-offset-4 hover:text-blue-700 hover:underline focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600">{{ $booking->reference }}</a>
            <p class="mt-1.5 text-xs leading-5 text-slate-500 md:hidden">{{ $booking->customer_name }}</p>
            <x-bookings.status-badge :status="$booking->status->value" class="mt-2 sm:hidden" />
        </td>
        <td class="hidden px-5 py-5 text-slate-600 md:table-cell">{{ $booking->customer_name }}</td>
        <td class="hidden px-5 py-5 sm:table-cell">
            <x-bookings.status-badge :status="$booking->status->value" />
        </td>
        <td class="px-5 py-5 text-right font-medium text-slate-700 tabular-nums sm:px-7">£{{ number_format($booking->total(), 2) }}</td>
    </tr>
@empty
    <tr>
        <td colspan="4" class="px-5 py-14 text-center">
            <p class="font-medium text-slate-800">No bookings yet</p>
            <p class="mt-2 text-sm text-slate-500">Create a demo booking above to see the workflow in action.</p>
        </td>
    </tr>
@endforelse
