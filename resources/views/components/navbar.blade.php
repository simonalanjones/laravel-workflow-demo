@props([
    'activeNavigation' => 'bookings'
])

<nav aria-label="Main navigation" class="mx-auto max-w-[1440px] px-5 sm:px-10">
    <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-3 border-b border-slate-200 py-4 dark:border-white/10">
        <a href="{{ route('home') }}" class="flex items-center gap-2 rounded-sm text-sm font-semibold tracking-tight text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500 dark:text-white">
            <svg class="size-6 shrink-0 text-indigo-600 dark:text-indigo-400" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path d="m14 9-7 14m12-14 7 14M10 25h12M12 19l11 4" />
                <circle cx="16" cy="6" r="4" /><circle cx="6" cy="26" r="4" /><circle cx="26" cy="26" r="4" />
            </svg>
            Laravel Workflow Demo
        </a>
        <div class="flex items-center gap-2">
            <a href="{{ route('bookings.index') }}" @if ($activeNavigation === 'bookings') aria-current="page" @endif
                @class([
                    'rounded-md px-3 py-2 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500',
                    'bg-white text-slate-900 dark:bg-gray-950/50 dark:text-white' => $activeNavigation === 'bookings',
                    'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => $activeNavigation !== 'bookings',
                ])>Bookings</a>
            <a href="{{ url('/bookings/create') }}"
                @if ($activeNavigation === 'create') aria-current="page" @endif
                @class([
                    'rounded-md px-3 py-2 text-sm font-medium focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500',
                    'bg-white text-slate-900 dark:bg-gray-950/50 dark:text-white' => $activeNavigation === 'create',
                    'text-slate-600 hover:bg-slate-200/60 hover:text-slate-900 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white' => $activeNavigation !== 'create',
                ])>Create booking</a>
        </div>
    </div>
</nav>
