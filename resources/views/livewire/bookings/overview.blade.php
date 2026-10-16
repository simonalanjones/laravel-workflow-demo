<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    #[Computed]
    public function bookings(): LengthAwarePaginator
    {
        return Booking::with(['items', 'workflows'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);
    }

    /**
     * @return array{pending: int, processing: int, processed: int, failed: int}
     */
    #[Computed]
    public function counts(): array
    {
        return [
            'pending' => Booking::where('status', BookingStatus::Pending)->count(),
            'processing' => Booking::where('status', BookingStatus::Processing)->count(),
            'processed' => Booking::where('status', BookingStatus::Processed)->count(),
            'failed' => Booking::where('status', BookingStatus::Failed)->count(),
        ];
    }
};
?>

<div wire:poll.2s>
    <section aria-labelledby="bookings-title"
        class="w-full max-w-6xl overflow-hidden rounded-xl border border-slate-200 bg-white dark:border-white/10 dark:bg-gray-900">
        <div class="flex flex-wrap items-start justify-between gap-3 bg-slate-50/80 px-5 py-5 sm:px-7 dark:bg-white/[0.03]">
            <div class="flex flex-col gap-3">
                <div class="flex items-baseline gap-2">
                    <h2 id="bookings-title" class="text-base font-semibold text-slate-900 dark:text-white">All bookings</h2>
                    <span class="text-xs text-slate-500 dark:text-gray-400">· {{ $this->bookings->total() }} total</span>
                </div>
                <dl class="flex flex-wrap gap-x-5 gap-y-2 text-xs">
                    @foreach ($this->counts as $status => $count)
                        <div class="flex items-baseline gap-1.5">
                            <dt class="text-slate-500 dark:text-gray-400">{{ ucfirst($status) }}</dt>
                            <dd class="font-semibold tabular-nums {{ $status === 'failed' && $count > 0 ? 'text-red-700 dark:text-red-300' : 'text-slate-700 dark:text-gray-200' }}">{{ $count }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
            <p class="text-xs text-slate-500 dark:text-gray-400">Latest first · updates automatically</p>
        </div>
        <table class="w-full table-fixed border-t border-slate-200 text-left text-sm dark:border-white/10">
            <colgroup>
                <col class="w-[68%] sm:w-[38%]">
                <col class="hidden sm:table-column sm:w-[42%]">
                <col class="w-[32%] sm:w-[20%]">
            </colgroup>
            <thead class="sr-only">
                <tr>
                    <th scope="col">Amount, status and current step</th>
                    <th scope="col" class="hidden sm:table-cell">Customer and booking reference</th>
                    <th scope="col">Booking details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                @include('partials.booking-rows', ['bookings' => $this->bookings])
            </tbody>
        </table>
        <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7 dark:border-white/10">
            <p class="text-sm text-slate-500 dark:text-gray-400">
                Showing {{ $this->bookings->firstItem() ?? 0 }}
                to {{ $this->bookings->lastItem() ?? 0 }}
                of {{ $this->bookings->total() }} results
            </p>

            @if ($this->bookings->hasPages())
                <div class="w-fit [&_nav]:gap-2 [&_button:enabled:hover]:bg-slate-100 [&_button:enabled:hover]:text-slate-900 dark:[&_button:enabled:hover]:bg-gray-700 dark:[&_button:enabled:hover]:text-white">
                    {{ $this->bookings->links('livewire::simple-tailwind') }}
                </div>
            @endif
        </div>
    </section>
</div>
