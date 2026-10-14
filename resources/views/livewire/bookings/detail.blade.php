<div wire:poll.1s x-data="{ showEventTypes: false, showFullHistory: false }">
    <nav aria-label="Booking navigation" class="mb-9 flex flex-wrap items-center justify-between gap-3 text-sm">
        <a href="/"
            class="inline-flex items-center gap-2 rounded-sm text-slate-500 dark:text-gray-400 transition hover:text-slate-900 dark:hover:text-white focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500">
            <x-icons.arrow-left class="size-4" />
            Bookings
        </a>
    </nav>

    <x-bookings.header :booking="$booking" />

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:gap-x-12">
        <div class="grid min-w-0 gap-10 sm:gap-12">
            <x-bookings.workflow.progress :workflows="$booking->workflows" :progress="$workflowProgress" :selected-workflow-id="$selectedWorkflowId" />
            <x-bookings.items :items="$booking->items" :total="$booking->total()" />
        </div>

        <x-bookings.activity.history :activities="$activities" :workflows="$booking->workflows" :selected-workflow="$selectedWorkflow" :selected-workflow-id="$selectedWorkflowId" />
    </div>
</div>
