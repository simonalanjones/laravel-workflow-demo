<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <meta name="description" content="Explore queued jobs, retries and idempotent requests through Simon Jones's interactive Laravel booking workflow demo.">
    <title>Laravel Workflow Demo — Simon Jones</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-slate-50 text-slate-600 antialiased scheme-light dark:bg-gray-900 dark:text-gray-400 dark:scheme-dark">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-5 focus:top-3 focus:z-20 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-indigo-800">Skip to content</a>

    <header class="border-b border-slate-200/80 bg-white dark:border-white/10 dark:bg-gray-900">
        <nav aria-label="Main navigation" class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-5 px-5 py-5 sm:px-10">
            <a href="{{ route('home') }}" aria-current="page" class="flex items-center gap-3 rounded-sm text-base font-bold tracking-tight text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500 sm:text-lg dark:text-white">
                <svg class="size-8 shrink-0 text-indigo-600 dark:text-indigo-400" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="m14 9-7 14m12-14 7 14M10 25h12M12 19l11 4" />
                    <circle cx="16" cy="6" r="4" /><circle cx="6" cy="26" r="4" /><circle cx="26" cy="26" r="4" />
                </svg>
                Laravel Workflow Demo
            </a>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-4 text-sm font-medium sm:gap-x-8">
                <a href="#how-it-works" class="rounded-sm hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500 dark:hover:text-white">How it works</a>
                <a href="#technology" class="rounded-sm hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500 dark:hover:text-white">Technology</a>
                <a href="https://github.com/simonalanjones/laravel-workflow-demo" class="rounded-sm hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500 dark:hover:text-white">GitHub</a>
                <a href="{{ route('bookings.index') }}" class="rounded-md bg-indigo-600 px-4 py-2.5 font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400">Try the demo</a>
            </div>
        </nav>
    </header>

    <main id="main-content">
        <section aria-labelledby="hero-heading" class="mx-auto grid max-w-7xl items-center gap-12 px-5 py-14 sm:px-10 sm:py-20 lg:grid-cols-2 lg:gap-12 lg:py-24">
            <div>
                <p class="text-xs font-semibold tracking-[0.18em] text-indigo-600 dark:text-indigo-400">AN INTERACTIVE LARAVEL PROJECT</p>
                <h1 id="hero-heading" class="mt-6 text-4xl font-bold leading-[1.12] tracking-tight text-slate-950 sm:text-5xl xl:text-6xl dark:text-white">
                    Real workflows.<br>Unreliable APIs.<br><span class="text-indigo-600 dark:text-indigo-400">Reliable recovery.</span>
                </h1>
                <p class="mt-7 max-w-xl text-lg leading-8 text-slate-600 dark:text-gray-300">Explore queued jobs, retries and idempotent requests through an interactive booking workflow.</p>
                <div class="mt-9 flex flex-wrap gap-3">
                    <a href="{{ route('bookings.index') }}" class="inline-flex items-center gap-4 rounded-md bg-indigo-600 px-5 py-3.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:bg-indigo-500 dark:hover:bg-indigo-400">Try the demo <span aria-hidden="true">&rarr;</span></a>
                    <a href="https://github.com/simonalanjones/laravel-workflow-demo" class="rounded-md border border-slate-300 bg-white px-5 py-3.5 text-sm font-semibold text-slate-900 shadow-sm hover:bg-slate-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:border-white/15 dark:bg-white/5 dark:text-white dark:hover:bg-white/10">View source code</a>
                </div>
                <p class="mt-9 text-sm leading-6 text-slate-500 dark:text-gray-400">Built by Simon Jones <span aria-hidden="true">&middot;</span> A learning project, made observable.</p>
            </div>

            <aside aria-labelledby="preview-heading" class="rounded-xl border border-slate-200 bg-white p-5 shadow-xl shadow-slate-200/50 sm:p-7 dark:border-white/10 dark:bg-gray-800 dark:shadow-black/20">
                <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 pb-5 dark:border-white/10">
                    <div>
                        <p class="mb-2 text-xs font-medium tracking-wide text-slate-500 dark:text-gray-400">EXAMPLE WORKFLOW</p>
                        <h2 id="preview-heading" class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white">Booking workflow</h2>
                        <p class="mt-1 text-sm">Reference: BK-1042</p>
                    </div>
                    <span class="inline-flex items-center gap-2 rounded-md bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-800 ring-1 ring-inset ring-amber-600/10 dark:bg-amber-400/10 dark:text-amber-300 dark:ring-amber-400/20"><span class="size-2 rounded-full bg-amber-500"></span>In progress</span>
                </div>
                <ol class="divide-y divide-slate-100 dark:divide-white/10">
                    @foreach (['Hotel reservation', 'Flight reservation'] as $reservation)
                        <li class="flex items-center gap-3 py-4">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white dark:bg-emerald-500"><x-icons.check class="size-4" /></span>
                            <span class="flex flex-1 flex-wrap items-center justify-between gap-2">
                                <span class="text-sm font-medium text-slate-900 dark:text-white">{{ $reservation }}</span>
                                <span class="rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300">Completed</span>
                            </span>
                        </li>
                    @endforeach
                    <li class="flex items-center gap-3 py-4">
                        <span aria-hidden="true" class="flex size-6 shrink-0 items-center justify-center rounded-full bg-amber-500 text-sm font-bold text-white">&hellip;</span>
                        <span class="flex flex-1 flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Car rental</span>
                            <span class="rounded-md bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 dark:bg-amber-400/10 dark:text-amber-300">Response unknown</span>
                        </span>
                    </li>
                    <li class="flex items-center gap-3 py-4">
                        <span aria-hidden="true" class="size-6 shrink-0 rounded-full border-2 border-slate-300 dark:border-gray-500"></span>
                        <span class="flex flex-1 flex-wrap items-center justify-between gap-2">
                            <span class="text-sm font-medium text-slate-900 dark:text-white">Operations signoff</span>
                            <span class="rounded-md bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600 dark:bg-white/5 dark:text-gray-300">Pending</span>
                        </span>
                    </li>
                </ol>
                <div class="mt-3 flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-400/20 dark:bg-amber-400/5">
                    <x-icons.exclamation-triangle class="size-5 text-amber-600 dark:text-amber-400" />
                    <div>
                        <h3 class="text-sm font-semibold text-amber-950 dark:text-amber-200">The response was lost. What happens next?</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-gray-300">Retry with the same idempotency key to recover the result from the demo provider.</p>
                        <a href="{{ route('bookings.create', ['scenario' => 'lost-response']) }}" class="mt-3 inline-flex rounded-md border border-indigo-300 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:border-indigo-400/40 dark:bg-indigo-400/10 dark:text-indigo-300 dark:hover:bg-indigo-400/20">Try this scenario <span class="ml-2" aria-hidden="true">&rarr;</span></a>
                    </div>
                </div>
                <div class="mt-5 border-t border-slate-100 pt-4 dark:border-white/10">
                    <p class="text-sm font-semibold text-slate-900 dark:text-white">Activity history</p>
                    <p class="mt-3 flex items-center gap-2 text-xs leading-5"><span class="size-2 shrink-0 rounded-full bg-amber-500"></span>Attempt 1 <span aria-hidden="true">&middot;</span> Response lost; awaiting retry</p>
                </div>
            </aside>
        </section>

        <section id="how-it-works" aria-labelledby="features-heading" class="mx-auto max-w-7xl scroll-mt-8 px-5 pb-14 sm:px-10 sm:pb-16">
            <div class="border-t border-slate-200 pt-12 dark:border-white/10">
                <p class="text-xs font-semibold tracking-[0.18em] text-indigo-600 dark:text-indigo-400">UNDER THE HOOD</p>
                <h2 id="features-heading" class="mt-4 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-white">More than a happy-path demo.</h2>
                <div class="mt-8 grid gap-5 md:grid-cols-3">
                    @foreach ([
                        ['title' => 'Queued workflows', 'description' => 'Follow independent jobs as a booking progresses.', 'path' => 'm12 3 9 5-9 5-9-5 9-5Zm-9 9 9 5 9-5M3 16l9 5 9-5'],
                        ['title' => 'Safe retries', 'description' => 'See how a supporting provider handles the same request twice.', 'path' => 'M20 7v5h-5M4 17v-5h5M5.5 7a7.5 7.5 0 0 1 12-2L20 8M4 16l2.5 3a7.5 7.5 0 0 0 12-2'],
                        ['title' => 'Visible outcomes', 'description' => 'Inspect attempts, responses and workflow activity.', 'path' => 'M7 3h10a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm2 5h6m-6 4h6m-6 4h3'],
                    ] as $feature)
                        <article class="rounded-lg border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-gray-800/50">
                            <span class="inline-flex rounded-lg bg-indigo-50 p-3 text-indigo-600 dark:bg-indigo-400/10 dark:text-indigo-400">
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $feature['path'] }}" /></svg>
                            </span>
                            <h3 class="mt-4 text-lg font-semibold tracking-tight text-slate-900 dark:text-white">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-base leading-7">{{ $feature['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="technology" aria-labelledby="technology-heading" class="scroll-mt-8 border-y border-slate-200/60 bg-slate-100/70 dark:border-white/5 dark:bg-gray-800/40">
            <div class="mx-auto max-w-7xl px-5 py-12 sm:px-10">
                <h2 id="technology-heading" class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Built with familiar tools.</h2>
                <dl class="mt-8 grid gap-7 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ([['Laravel', 'Workflow orchestration', 'La', 'laravel.svg'], ['Livewire', 'Reactive interface', 'Lw', 'livewire.svg'], ['Tailwind CSS', 'Component styling', 'Tw', 'tailwind.svg'], ['Pest', 'Behaviour tests', 'Pe', 'pest.svg']] as [$technology, $purpose, $initials, $logo])
                        <div class="flex items-center gap-4">
                            <span aria-hidden="true" class="flex size-12 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-white font-mono text-lg font-semibold text-indigo-600 shadow-sm dark:border-white/10 dark:bg-gray-800 dark:text-indigo-400">
                                @if ($logo)
                                    <img src="{{ Vite::asset('resources/images/logos/'.$logo) }}" alt="" width="32" height="32" class="size-8 object-contain {{ $logo === 'pest.svg' ? 'invert dark:invert-0' : '' }}" loading="lazy">
                                @else
                                    {{ $initials }}
                                @endif
                            </span>
                            <div>
                                <dt class="text-sm font-semibold text-slate-900 dark:text-white">{{ $technology }}</dt>
                                <dd class="mt-1 text-xs leading-5">{{ $purpose }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>
            </div>
        </section>
    </main>

    <footer class="mx-auto flex max-w-7xl flex-col justify-between gap-3 px-5 py-6 text-xs leading-5 sm:flex-row sm:px-10">
        <p>Laravel Workflow Demo <span aria-hidden="true">&middot;</span> By Simon Jones</p>
        <a href="{{ route('bookings.index') }}" class="rounded-sm font-semibold text-indigo-600 hover:text-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Explore the demo <span aria-hidden="true">&rarr;</span></a>
    </footer>
</body>

</html>
