@php
    $bookingItems = [
        'accommodation' => 'Accommodation',
        'flight' => 'Flights',
        'car_hire' => 'Car hire',
    ];

    $bookingOptions = [
        [
            'name' => 'requires_signoff',
            'label' => 'Require operations signoff',
            'description' => 'Uncheck to complete the booking automatically once all items are confirmed.',
            'default' => true,
        ],
        [
            'name' => 'simulate_network_delay',
            'label' => 'Simulate network delay',
            'description' =>
                'Adds a random 1-5 second wait to each provider request, including retries. Signoff is unaffected.',
            'default' => false,
        ],
        [
            'name' => 'simulate_lost_response',
            'label' => 'Simulate lost provider response',
            'description' =>
                'Each provider saves its first confirmation but simulates a failed response. A retry retrieves that same confirmation without booking again. Signoff is unaffected.',
            'default' => false,
        ],
    ];
@endphp

<x-layout>
    <x-slot:title>
        New booking - Laravel Workflow Demo
    </x-slot:title>

    <div aria-labelledby="demo-title"
        class="mb-8 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-gray-800 p-5 sm:p-7">
        <h2 id="demo-title" class="text-base/7 font-semibold text-slate-900 dark:text-white">Try a booking workflow</h2>
        <p class="mt-1 text-sm/6 text-slate-500 dark:text-gray-400">Create a demo booking and watch its steps progress.
        </p>

        <form method="POST" action="/demo/bookings/successful" class="mt-8 grid gap-6">
            @csrf
            <fieldset class="flex flex-wrap gap-x-6 gap-y-4 border-b border-slate-200 dark:border-white/10 pb-6">
                <legend class="mb-4 text-sm/6 font-semibold text-slate-900 dark:text-white">Choose booking items</legend>

                @foreach ($bookingItems as $type => $label)
                    <label
                        class="flex cursor-pointer items-start gap-3 text-sm/6 font-medium text-slate-900 dark:text-white">
                        <span class="flex h-6 shrink-0 items-center">
                            <x-forms.checkbox :id="'item_type_' . $type" name="item_types[]" :value="$type"
                                :checked="in_array($type, (array) old('item_types', []), true)" />
                        </span>
                        {{ $label }}
                    </label>
                @endforeach
                @error('item_types')
                    <p role="alert" class="w-full text-sm text-red-700 dark:text-red-300">
                        {{ $message }}
                    </p>
                @enderror
            </fieldset>

            @foreach ($bookingOptions as $option)
                <div>
                    <input type="hidden" name="{{ $option['name'] }}" value="0">
                    <label for="{{ $option['name'] }}"
                        class="flex cursor-pointer items-start gap-3 text-sm/6 font-medium text-slate-900 dark:text-white">
                        <span class="flex h-6 shrink-0 items-center">
                            <x-forms.checkbox :id="$option['name']" :name="$option['name']" value="1" :checked="(bool) old($option['name'], $option['default'])"
                                :aria-describedby="$option['name'] . '-description'" />
                        </span>
                        {{ $option['label'] }}
                    </label>
                    <p id="{{ $option['name'] }}-description" class="pl-7 text-sm/6 text-slate-500 dark:text-gray-400">
                        {{ $option['description'] }}</p>
                </div>
            @endforeach

            <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 dark:border-white/10 pt-6">

                <x-forms.button type="submit" variant="primary" class="w-full sm:w-auto">
                    Create successful booking
                </x-forms.button>

                <x-forms.button type="submit" variant="secondary" class="w-full sm:w-auto"
                    formaction="/demo/bookings/failing">
                    Create failing booking
                </x-forms.button>

                <x-forms.button type="submit" variant="secondary" class="w-full sm:w-auto"
                    formaction="/demo/bookings/random-failure">
                    Create random-failure booking
                </x-forms.button>

            </div>
            <p class="text-sm/6 text-slate-500 dark:text-gray-400">Random failures can affect accommodation, flights, or
                car hire independently on each attempt. Signoff is excluded. The failing-booking scenario always fails
                flights.</p>
        </form>
    </div>
</x-layout>
