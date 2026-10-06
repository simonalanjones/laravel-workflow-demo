<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Booking {{ $booking->reference }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100 text-gray-900">
    <main class="mx-auto max-w-4xl px-6 py-10">

        <livewire:booking-detail :booking="$booking" />

    </main>
</body>
</html>