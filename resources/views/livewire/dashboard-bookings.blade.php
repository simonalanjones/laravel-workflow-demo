<div wire:poll.2s>
    <div class="mb-9 flex flex-wrap items-center justify-between gap-3 text-sm">
        <span class="text-slate-500">Laravel Workflow Demo</span>
        <div class="flex items-center gap-4">
            <span class="hidden text-xs text-slate-500 sm:inline">Updated <time>{{ now()->format('H:i:s') }}</time></span>
            <button type="button" wire:click="$refresh" wire:loading.attr="disabled" class="cursor-pointer rounded-md px-2 py-1 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-blue-500 disabled:opacity-50">Refresh</button>
        </div>
    </div>

    <header class="mb-8 border-b border-slate-200/80 pb-8">
        <p class="mb-3 text-xs font-medium tracking-wide text-slate-500">Workflow overview</p>
        <h1 class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl">Bookings</h1>
        <p class="mt-3 text-sm leading-6 text-slate-500">Follow each booking from its first queued job to final signoff.</p>
    </header>

    <dl class="mb-8 grid grid-cols-2 gap-x-6 gap-y-6 sm:grid-cols-4">
        @foreach ($counts as $status => $count)
            <div class="border-l border-slate-200 pl-5 first:border-slate-300">
                <dt class="text-xs text-slate-500">{{ ucfirst($status) }}</dt>
                <dd class="mt-2 text-3xl font-semibold tracking-tight tabular-nums {{ $status === 'failed' && $count > 0 ? 'text-red-700' : 'text-slate-900' }}">{{ $count }}</dd>
            </div>
        @endforeach
    </dl>

    <section aria-labelledby="demo-title" class="mb-8 flex flex-col justify-between gap-5 rounded-xl border border-slate-200/80 bg-white p-5 sm:p-6 xl:flex-row xl:items-center">
        <div>
            <h2 id="demo-title" class="text-sm font-semibold text-slate-900">Try a booking workflow</h2>
            <p class="mt-1.5 text-xs leading-5 text-slate-500">Create a demo booking and watch its steps progress.</p>
        </div>
        <div class="flex flex-wrap gap-2.5">
            <form method="POST" action="/demo/bookings/successful" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="w-full cursor-pointer rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">Create successful booking</button>
            </form>
            <form method="POST" action="/demo/bookings/failing" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="w-full cursor-pointer rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">Create failing booking</button>
            </form>
            <form method="POST" action="/demo/bookings/random-failure" class="w-full sm:w-auto">
                @csrf
                <button type="submit" class="w-full cursor-pointer rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900">Create random-failure booking</button>
            </form>
        </div>
    </section>

    <section aria-labelledby="bookings-title" class="overflow-hidden rounded-xl border border-slate-200/80 bg-white">
        <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-5 sm:px-7">
            <h2 id="bookings-title" class="text-base font-semibold text-slate-900">All bookings <span class="ml-2 text-xs font-normal text-slate-500">{{ $bookings->count() }}</span></h2>
            <p class="text-xs text-slate-500">Latest first · updates automatically</p>
        </div>
        <table class="w-full table-fixed text-left text-sm">
            <thead class="border-y border-slate-100 bg-slate-50/70 text-xs text-slate-500">
                <tr>
                    <th scope="col" class="w-[60%] px-5 py-3 font-medium sm:w-auto sm:px-7">Booking</th>
                    <th scope="col" class="hidden px-5 py-3 font-medium md:table-cell">Customer</th>
                    <th scope="col" class="hidden px-5 py-3 font-medium sm:table-cell">Status</th>
                    <th scope="col" class="px-5 py-3 text-right font-medium sm:px-7">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @include('partials.booking-rows')
            </tbody>
        </table>
    </section>
</div>
