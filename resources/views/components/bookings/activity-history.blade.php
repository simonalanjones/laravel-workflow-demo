@props(['activities', 'workflows', 'selectedWorkflow' => null, 'selectedWorkflowId' => null])

<section aria-labelledby="history-title" {{ $attributes->class('min-w-0 py-1 lg:sticky lg:top-8') }}>
    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div><h2 id="history-title" class="text-base font-semibold text-slate-700">Recent activity</h2><p class="mt-1 text-xs text-slate-500">Latest first · updates automatically</p></div>
        <label class="max-w-full">
            <span class="sr-only">Filter processing history by workflow</span>
            <select x-on:change="$event.target.value === '' ? $wire.showAllActivity() : $wire.selectWorkflow(Number($event.target.value))" class="max-w-44 cursor-pointer rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-600 focus:border-blue-500 focus:outline-blue-500">
                <option value="" @selected($selectedWorkflowId === null)>All events</option>
                @foreach ($workflows as $workflow)
                    <option value="{{ $workflow->id }}" @selected($selectedWorkflowId === $workflow->id)>{{ ucfirst(str_replace('_', ' ', $workflow->type)) }}</option>
                @endforeach
            </select>
        </label>
    </div>
    @if ($selectedWorkflow)
        <div class="mb-5 flex items-center justify-between gap-3 border-b border-slate-200 pb-3 text-xs"><span class="text-slate-600">{{ ucfirst(str_replace('_', ' ', $selectedWorkflow->type)) }}</span><button type="button" wire:click="showAllActivity" class="shrink-0 cursor-pointer text-blue-600 hover:underline">Show all</button></div>
    @endif
    <ol x-bind:class="{ 'is-expanded': showFullHistory }" class="booking-activity lg:max-h-[calc(100vh-17rem)] lg:overflow-y-auto lg:pr-2">
        @forelse ($activities->reverse() as $activity)
            @php
                $isFailure = str_contains($activity->type, 'failed');
            @endphp
            <li wire:key="activity-{{ $activity->id }}" class="relative flex gap-4 pb-6 pl-1">
                @if (! $loop->last)<span aria-hidden="true" class="absolute top-4 bottom-0 left-[7px] w-px bg-slate-200"></span>@endif
                <span aria-hidden="true" class="relative z-10 mt-2 size-[7px] shrink-0 rounded-full ring-4 ring-slate-50 {{ $isFailure ? 'bg-red-400' : 'bg-slate-300' }}"></span>
                <div class="min-w-0 flex-1">
                    <p class="break-words text-sm leading-6 text-slate-600">{{ $activity->message }}</p>
                    <time class="mt-1 block text-[11px] text-slate-500 tabular-nums" title="{{ $activity->created_at->format('d M Y, H:i:s') }}" datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('d M · H:i:s') }}</time>
                    <p x-show="showEventTypes" style="display: none;" class="mt-1 break-all font-mono text-[11px] text-slate-500">{{ $activity->type }}</p>
                </div>
            </li>
        @empty
            <li class="rounded-lg border border-dashed border-slate-200 px-5 py-8 text-center text-sm text-slate-500">Activity will appear when processing begins.</li>
        @endforelse
    </ol>
    <div class="flex flex-wrap items-center justify-between gap-4 border-t border-slate-200/80 pt-4 text-xs">
        @if ($activities->count() > 7)
            <button type="button" x-on:click="showFullHistory = !showFullHistory" x-bind:aria-expanded="showFullHistory" class="cursor-pointer rounded-sm font-medium text-slate-600 hover:text-slate-900 focus-visible:outline-blue-500"><span x-show="!showFullHistory">Show full history ({{ $activities->count() }})</span><span x-show="showFullHistory" style="display: none;">Show recent only</span></button>
        @else
            <span class="text-slate-500">{{ $activities->count() }} {{ $activities->count() === 1 ? 'event' : 'events' }}</span>
        @endif
        <label class="inline-flex cursor-pointer items-center gap-2 text-slate-500"><input type="checkbox" x-model="showEventTypes" class="size-3.5 rounded border-slate-300 accent-slate-600"> Show event types</label>
    </div>
</section>
