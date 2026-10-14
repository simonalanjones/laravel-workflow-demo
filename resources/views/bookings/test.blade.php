<x-layout>

    <x-slot:title>
        injected the title from the test.blade.php file
    </x-slot:title>

    @section('heading')
        <h1 class="mb-4 text-2xl font-bold text-slate-900 dark:text-white">Test booking in section</h1>
    @endsection
</x-layout>
