<div wire:poll.1s>
<a
            href="/"
            class="text-sm text-blue-600 hover:underline"
        >
            ← Back to dashboard
        </a>

        <div class="mt-6 rounded-lg bg-white p-6 shadow">
            <div class="flex items-start justify-between">
                <div>
                    <h1 class="text-3xl font-bold">
                        {{ $booking->reference }}
                    </h1>
                    <p class="mt-1 text-gray-600">
                        {{ $booking->customer_name }}
                    </p>
                    <p class="text-gray-600">
                        {{ $booking->customer_email }}
                    </p>
                </div>

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
                

            </div>

            <div class="mt-8">

                <h2 class="text-lg font-semibold">
                    Booking items
                </h2>

                <div class="mt-4 divide-y divide-gray-200">

                    @foreach ($booking->items as $item)

                        <div class="flex justify-between py-3">

                            <div>
                                {{ $item->description }}
                                × {{ $item->quantity }}
                            </div>

                            <div>
                                £{{ number_format($item->quantity * $item->unit_price, 2) }}
                            </div>

                        </div>

                    @endforeach

                </div>

                <div class="mt-4 flex justify-between border-t pt-4 font-semibold">

                    <span>Total</span>

                    <span>
                        £{{ number_format($booking->total(), 2) }}
                    </span>

                </div>

            </div>

        </div>
        <div class="mt-8">

            <div class="mt-8">
    <h2 class="text-lg font-semibold">
        Child workflows
    </h2>

    <div class="mt-4 space-y-3">
        @foreach ($booking->workflows as $workflow)
            <div
    wire:click="selectWorkflow({{ $workflow->id }})"
    class="cursor-pointer rounded-lg border p-4
        {{ $selectedWorkflowId === $workflow->id
            ? 'border-blue-500 bg-blue-50'
            : 'border-gray-200' }}">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <div class="font-medium">
                            {{ str_replace('_', ' ', ucfirst($workflow->type)) }}
                        </div>

                        <div
                            class="mt-1 text-sm text-gray-500">
                            {{ $workflow->message }}
                        </div>
                    </div>

                    <div class="text-sm font-medium">
                        {{ ucfirst($workflow->status) }}
                    </div>
                </div>
                @if ($workflow->status === 'failed')
                    <button
                        type="button"
                        wire:click.stop="retryWorkflow({{ $workflow->id }})"
                        class="mt-3 rounded-md bg-amber-600 px-4 py-2 text-sm font-medium text-white hover:bg-amber-700"
                    >
                        Retry workflow
                    </button>
                @endif
                @if (
                    $workflow->type === 'operations_signoff'
                    && $workflow->status === 'awaiting_approval'
                )
                    <button
                        type="button"
                        wire:click.stop="approveWorkflow({{ $workflow->id }})"
                        class="mt-3 rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700"
                    >
                        Approve signoff
                    </button>
                @endif
            </div>
        @endforeach
    </div>
</div>


    <div class="mt-8 flex items-center justify-between">
    <h2 class="text-lg font-semibold">
        @if ($selectedWorkflow)
            {{ str_replace('_', ' ', ucfirst($selectedWorkflow->type)) }}
            activity
        @else
            Processing history
        @endif
    </h2>

    @if ($selectedWorkflow)
        <button
            type="button"
            wire:click="showAllActivity"
            class="text-sm text-blue-600 hover:underline"
        >
            Show all activity
        </button>
    @endif
</div>

    <div class="mt-6">
        @foreach ($activities as $activity)
            <div class="relative flex gap-4 pb-6">
                @if (! $loop->last)
                    <div class="absolute left-[5px] top-3 h-full border-l border-gray-300"></div>
                @endif

                <div class="relative z-10 mt-1.5 h-3 w-3 rounded-full bg-gray-400"></div>
                <div class="flex-1">

                    <div class="flex items-baseline justify-between gap-4">

                        <div class="font-medium">
                            {{ $activity->message }}
                        </div>

                        <div class="text-sm text-gray-500">
                            {{ $activity->created_at->format('H:i:s') }}
                        </div>
                    </div>

                    <div class="mt-1 text-sm text-gray-500">
                        {{ $activity->type }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
</div>