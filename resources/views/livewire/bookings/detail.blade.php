<div wire:poll.1s x-data="{ showEventTypes: false, showFullHistory: false }"
    class="rounded-xl border border-slate-200 bg-white px-5 py-6 sm:px-7 sm:py-8 dark:border-white/10 dark:bg-gray-900">

    <x-bookings.header :booking="$booking" />

    <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:gap-x-12">
        <div class="grid min-w-0 gap-10 sm:gap-12">
            <x-bookings.workflow.progress :workflows="$booking->workflows" :progress="$workflowProgress" :selected-workflow-id="$selectedWorkflowId" />
            <x-bookings.items :items="$booking->items" :total="$booking->total()" />
        </div>

        <x-bookings.activity.history :activities="$activities" :workflows="$booking->workflows" :selected-workflow="$selectedWorkflow" :selected-workflow-id="$selectedWorkflowId" />
    </div>
</div>
