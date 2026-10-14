<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Laravel Workflow Demo' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body
    class="flex min-h-screen flex-col bg-slate-50 dark:bg-gray-900 text-slate-700 dark:text-gray-300 antialiased scheme-light dark:scheme-dark">
    <main class="mx-auto w-full max-w-[1440px] flex-1 px-5 py-6 sm:px-10 sm:py-8">
        {{ $slot }}
    </main>
    @include('partials.project-footer')
</body>

</html>
