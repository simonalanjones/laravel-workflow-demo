@props(['heading', 'description' => null, 'activeNavigation' => 'bookings'])

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Laravel Workflow Demo' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="flex min-h-screen flex-col bg-slate-50 text-slate-700 antialiased scheme-light dark:bg-gray-900 dark:text-gray-300 dark:scheme-dark">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-5 focus:top-3 focus:z-20 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-indigo-800">Skip to content</a>

    <div class="relative bg-slate-100 pb-24 dark:bg-gray-800/50">
        <nav aria-label="Main navigation" class="mx-auto max-w-[1440px] px-5 sm:px-10">
            <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-3 border-b border-slate-200 py-4 dark:border-white/10">
                <a href="{{ url('/') }}" class="rounded-sm text-sm font-semibold tracking-tight text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500 dark:text-white">
                    Laravel Workflow Demo
                </a>
                <div class="flex items-center gap-2">
                    <a href="{{ url('/') }}" @if ($activeNavigation === 'bookings') aria-current="page" @endif
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

        <header class="mx-auto max-w-[1440px] px-5 pb-8 pt-6 sm:px-10 sm:pt-8">
            <p class="mb-2 text-xs font-medium tracking-wide text-slate-500 dark:text-gray-400">Workflow overview</p>
            <h1 class="text-2xl/7 font-bold tracking-tight text-slate-900 sm:text-3xl dark:text-white">{{ $heading }}</h1>
            @if ($description)
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600 dark:text-gray-400">{{ $description }}</p>
            @endif
        </header>
    </div>

    <main id="main-content" class="relative -mt-24 mx-auto w-full max-w-[1440px] flex-1 px-5 pb-6 sm:px-10 sm:pb-8">
        {{ $slot }}
    </main>

    @include('partials.project-footer')
</body>

</html>
