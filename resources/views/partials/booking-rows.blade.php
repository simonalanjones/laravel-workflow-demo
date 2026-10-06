@foreach ($bookings as $booking)
    <tr>
        <td class="px-6 py-4 font-medium">
    <a
        href="{{ url('/bookings/' . $booking->id) }}"
        class="text-blue-600 hover:underline"
    >
        {{ $booking->reference }}
    </a>
</td>

        <td class="px-6 py-4">
            {{ $booking->customer_name }}
        </td>

        <td class="px-6 py-4">
            @php
                $statusClasses = match ($booking->status) {
                    \App\Enums\BookingStatus::Pending =>
                        'bg-gray-100 text-gray-700',

                    \App\Enums\BookingStatus::Processing =>
                        'bg-blue-100 text-blue-700',

                    \App\Enums\BookingStatus::Processed =>
                        'bg-green-100 text-green-700',

                    \App\Enums\BookingStatus::Failed =>
                        'bg-red-100 text-red-700',
                };
            @endphp

    <span class="inline-flex rounded-full px-3 py-1 text-sm font-medium {{ $statusClasses }}">
        {{ ucfirst($booking->status->value) }}
    </span>
</td>

        <td class="px-6 py-4 text-right">
            £{{ number_format($booking->total(), 2) }}
        </td>
    </tr>
@endforeach