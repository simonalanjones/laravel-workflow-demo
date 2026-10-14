<div wire:poll.2s>
    <div class="mb-9 flex flex-wrap items-center justify-between gap-3 text-sm">
        <span class="text-slate-500 dark:text-gray-400">Laravel Workflow Demo</span>
    </div>

    <header class="mb-8 border-b border-slate-200 dark:border-white/10 pb-8">
        <p class="mb-3 text-xs font-medium tracking-wide text-slate-500 dark:text-gray-400">Workflow overview</p>
        <h1 class="text-2xl/7 font-bold text-slate-900 dark:text-white sm:text-3xl sm:tracking-tight">Bookings</h1>
        <p class="mt-3 text-sm leading-6 text-slate-500 dark:text-gray-400">Follow each booking from its first queued job
            to completion.</p>
    </header>

    <dl class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($counts as $status => $count)
            <div class="overflow-hidden rounded-lg bg-white dark:bg-gray-800 px-4 py-5 shadow-sm sm:p-6">
                <dt class="truncate text-sm font-medium text-slate-500 dark:text-gray-400">{{ ucfirst($status) }}</dt>
                <dd
                    class="mt-1 text-3xl font-semibold tracking-tight tabular-nums {{ $status === 'failed' && $count > 0 ? 'text-red-700 dark:text-red-300' : 'text-slate-900 dark:text-white' }}">
                    {{ $count }}</dd>
            </div>
        @endforeach
    </dl>

    <section aria-labelledby="bookings-title"
        class="overflow-hidden rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-gray-800">
        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-5 sm:px-7">
            <h2 id="bookings-title" class="text-base font-semibold text-slate-900 dark:text-white">All bookings</h2>
            <p class="text-xs text-slate-500 dark:text-gray-400">Latest first · updates automatically</p>
        </div>
        <table class="w-full table-fixed divide-y divide-slate-200 dark:divide-white/15 text-left text-sm">
            <thead class="text-sm text-slate-900 dark:text-white">
                <tr>
                    <th scope="col" class="w-[60%] px-5 py-3.5 font-semibold sm:w-auto sm:px-7">Booking</th>
                    <th scope="col" class="hidden px-5 py-3.5 font-semibold md:table-cell">Customer</th>
                    <th scope="col" class="hidden px-5 py-3.5 font-semibold sm:table-cell">Status</th>
                    <th scope="col" class="px-5 py-3.5 text-right font-semibold sm:px-7">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-white/10">
                @include('partials.booking-rows')
            </tbody>
        </table>
       <div class="flex flex-col gap-4 border-t border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-7 dark:border-white/10">
    <p class="text-sm text-slate-500 dark:text-gray-400">
        Showing {{ $bookings->firstItem() ?? 0 }}
        to {{ $bookings->lastItem() ?? 0 }}
        of {{ $bookings->total() }} results
    </p>

    @if ($bookings->hasPages())
        <div class="w-fit [&_nav]:gap-2 [&_button:enabled:hover]:bg-slate-100 [&_button:enabled:hover]:text-slate-900 dark:[&_button:enabled:hover]:bg-gray-700 dark:[&_button:enabled:hover]:text-white">
            {{ $bookings->links('livewire::simple-tailwind') }}
        </div>
    @endif
</div>
    </section>
</div>
