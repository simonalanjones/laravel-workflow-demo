@props(['activities', 'workflows', 'selectedWorkflow' => null, 'selectedWorkflowId' => null])

<section aria-labelledby="history-title" {{ $attributes->class('min-w-0 py-1 lg:sticky lg:top-8') }}>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 id="history-title" class="text-sm/6 font-semibold text-slate-900 dark:text-white">Recent activity</h2>
            <p class="mt-1 text-xs text-slate-500 dark:text-gray-400">Latest first · updates automatically</p>
        </div>
        <x-bookings.activity.filter :workflows="$workflows" :selected-workflow="$selectedWorkflow" :selected-workflow-id="$selectedWorkflowId" />
    </div>

    <ol x-bind:class="{ 'is-expanded': showFullHistory }"
        class="booking-activity mb-6 lg:max-h-[calc(100vh-17rem)] lg:overflow-y-auto lg:pr-2">
        @forelse ($activities->reverse() as $activity)
            <x-bookings.activity.entry :activity="$activity" :is-last="$loop->last" />

        @empty
            <li
                class="rounded-lg border border-dashed border-slate-200 dark:border-white/10 px-5 py-8 text-center text-sm text-slate-500 dark:text-gray-400">
                Activity will appear when processing begins.</li>
        @endforelse
    </ol>
    <div
        class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200 dark:border-white/10 pt-4 text-xs">
        @if ($activities->count() > 7)
            <button type="button" x-on:click="showFullHistory = !showFullHistory" x-bind:aria-expanded="showFullHistory"
                class="cursor-pointer rounded-sm font-medium text-slate-700 dark:text-gray-300 hover:text-slate-900 dark:hover:text-white focus-visible:outline-indigo-500"><span
                    x-show="!showFullHistory">Show full history ({{ $activities->count() }})</span><span
                    x-show="showFullHistory" style="display: none;">Show recent only</span></button>
        @else
            <span class="text-slate-500 dark:text-gray-400">{{ $activities->count() }}
                {{ $activities->count() === 1 ? 'event' : 'events' }}</span>
        @endif
        <label class="inline-flex cursor-pointer items-center gap-2 text-slate-500 dark:text-gray-400"><input
                type="checkbox" x-model="showEventTypes"
                class="size-3.5 rounded border-slate-300 dark:border-white/20 accent-indigo-500"> Show event
            types</label>
    </div>
</section>
