<div wire:poll.2s>
    <div class="mb-8">
        <h1 class="text-3xl font-bold">
            Booking Queue Dashboard
        </h1>
        <p class="mt-2 text-gray-600">
            Booking processing and queue status
        </p>
    </div>
    <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-lg bg-white p-5 shadow">
        <div class="text-sm text-gray-500">
            Pending
        </div>
        <div class="mt-2 text-3xl font-bold">
            {{ $counts['pending'] }}
        </div>
    </div>
    <div class="rounded-lg bg-white p-5 shadow">
        <div class="text-sm text-gray-500">
            Processing
        </div>
        <div class="mt-2 text-3xl font-bold text-blue-700">
            {{ $counts['processing'] }}
        </div>
    </div>
    <div class="rounded-lg bg-white p-5 shadow">
        <div class="text-sm text-gray-500">
            Processed
        </div>
        <div class="mt-2 text-3xl font-bold text-green-700">
            {{ $counts['processed'] }}
        </div>
    </div>
    <div class="rounded-lg bg-white p-5 shadow">
        <div class="text-sm text-gray-500">
            Failed
        </div>
        <div class="mt-2 text-3xl font-bold text-red-700">
            {{ $counts['failed'] }}
        </div>
    </div>
</div>

    <div class="mb-6 flex flex-wrap gap-3">
    <form method="POST" action="/demo/bookings/successful">
        @csrf

        <button
            type="submit"
            class="rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700"
        >
            Create successful booking
        </button>
    </form>
    <form method="POST" action="/demo/bookings/failing">
        @csrf

        <button
            type="submit"
            class="rounded bg-red-600 px-4 py-2 text-white hover:bg-red-700"
        >
            Create failing booking
        </button>
    </form>
    <form method="POST" action="/demo/bookings/random-failure">
        @csrf
        <button
            type="submit"
            class="rounded bg-amber-600 px-4 py-2 text-white hover:bg-amber-700"
        >
            Create random-failure booking
        </button>
    </form>
</div>
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="w-full text-left">
            <thead class="bg-gray-50 text-sm text-gray-600">
                <tr>
                    <th class="px-6 py-4 font-medium">Reference</th>
                    <th class="px-6 py-4 font-medium">Customer</th>
                    <th class="px-6 py-4 font-medium">Status</th>
                    <th class="px-6 py-4 text-right font-medium">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @include('partials.booking-rows')
            </tbody>
        </table>
    </div>
</div>