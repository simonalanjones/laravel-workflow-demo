@props([
    'heading',
    'description' => null,
    'activeNavigation' => 'bookings'
])

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <title>{{ $title ?? 'Laravel Workflow Demo' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-slate-50 text-slate-700 antialiased scheme-light dark:bg-gray-900 dark:text-gray-300 dark:scheme-dark">
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:left-5 focus:top-3 focus:z-20 focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-indigo-800">Skip to content</a>
    <div class="relative bg-slate-100 pb-24 dark:bg-gray-800/50">
        <x-navbar :active-navigation="$activeNavigation" />
        <header class="mx-auto max-w-[1440px] px-5 pb-8 pt-6 sm:px-10 sm:pt-10">
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
