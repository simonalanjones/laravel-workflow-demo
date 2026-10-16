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
            'default' => $isLostResponseScenario,
        ],
    ];
@endphp

<x-layout heading="Create booking" description="Choose booking items and a demo scenario to explore the workflow." active-navigation="create">
    <x-slot:title>
        New booking - Laravel Workflow Demo
    </x-slot:title>

    <div aria-labelledby="demo-title"
        class="w-full max-w-6xl rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-gray-900 p-5 sm:p-7">
        <h2 id="demo-title" class="text-base/7 font-semibold text-slate-900 dark:text-white">Try a booking workflow</h2>
        <p class="mt-1 text-sm/6 text-slate-500 dark:text-gray-400">Create a demo booking and watch its steps progress.
        </p>

        @if ($isLostResponseScenario)
            <aside aria-labelledby="scenario-title" class="mt-6 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-400/20 dark:bg-indigo-400/10">
                <h3 id="scenario-title" class="text-sm font-semibold text-indigo-900 dark:text-indigo-200">Lost response scenario</h3>
                <p class="mt-2 text-sm/6 text-indigo-800 dark:text-indigo-200">The demo provider completes the operation, but its response is lost. Choose your booking items, leave “Simulate lost provider response” checked, and select “Create successful booking”. Once the steps fail with an unknown outcome, retry them to recover the saved confirmations without booking again.</p>
            </aside>
        @endif

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

                <x-forms.button type="submit" variant="secondary" class="w-full disabled:cursor-not-allowed sm:w-auto"
                    :disabled="$isLostResponseScenario" aria-describedby="scenario-actions-description"
                    formaction="/demo/bookings/failing">
                    Create failing booking
                </x-forms.button>

                <x-forms.button type="submit" variant="secondary" class="w-full disabled:cursor-not-allowed sm:w-auto"
                    :disabled="$isLostResponseScenario" aria-describedby="scenario-actions-description"
                    formaction="/demo/bookings/random-failure">
                    Create random-failure booking
                </x-forms.button>

            </div>
            @if ($isLostResponseScenario)
                <p id="scenario-actions-description" class="text-sm/6 text-slate-500 dark:text-gray-400">Failing and random-failure bookings are disabled to keep this example focused on recovering a lost response.
                    <a href="{{ route('bookings.create') }}" class="font-medium text-indigo-600 underline underline-offset-4 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">Open the standard form</a> to explore other scenarios.</p>
            @else
            <p id="scenario-actions-description" class="text-sm/6 text-slate-500 dark:text-gray-400">Random failures can affect accommodation, flights, or
                car hire independently on each attempt. Signoff is excluded. The failing-booking scenario always fails
                flights.</p>
            @endif
        </form>
    </div>
</x-layout>
