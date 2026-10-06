<div wire:poll.1s x-data="{ showEventTypes: false, showFullHistory: false }">
    <nav aria-label="Booking navigation" class="mb-9 flex flex-wrap items-center justify-between gap-3 text-sm">
        <a href="/" class="inline-flex items-center gap-2 rounded-sm text-slate-500 transition hover:text-slate-900 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-blue-600">
            @include('partials.workflow-icon', ['icon' => 'arrow', 'iconClass' => 'size-4'])
            Bookings
        </a>
        <div class="flex items-center gap-4">
            <span class="hidden text-xs text-slate-500 sm:inline">Updated <time>{{ now()->format('H:i:s') }}</time></span>
            <button type="button" wire:click="$refresh" wire:loading.attr="disabled" class="cursor-pointer rounded-md px-2 py-1 text-xs font-medium text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-blue-500 disabled:opacity-50">Refresh</button>
        </div>
    </nav>

    <x-bookings.header :booking="$booking" />

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)]">
        <div class="grid min-w-0 gap-7">
            <x-bookings.workflow-progress
                :workflows="$booking->workflows"
                :progress="$workflowProgress"
                :selected-workflow-id="$selectedWorkflowId"
            />
            <x-bookings.items :items="$booking->items" :total="$booking->total()" />
        </div>

        <x-bookings.activity-history
            :activities="$activities"
            :workflows="$booking->workflows"
            :selected-workflow="$selectedWorkflow"
            :selected-workflow-id="$selectedWorkflowId"
        />
    </div>
</div>
