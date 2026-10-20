<div wire:poll.1s x-data="{ showEventTypes: false, showFullHistory: false }">
    <x-bookings.header :booking="$booking" />
    <div
        class="rounded-xl border border-slate-200 bg-white px-5 py-6 sm:px-7 sm:py-8 dark:border-white/10 dark:bg-gray-900">
        <div class="grid items-start gap-8 lg:grid-cols-[minmax(0,1.5fr)_minmax(0,1fr)] lg:gap-x-12">
            <div class="grid min-w-0 gap-10 sm:gap-12">
                <x-bookings.workflow.progress
                    :workflow-steps="$booking->workflowSteps"
                    :progress="$workflowProgress"
                    :selected-workflow-step-id="$selectedWorkflowStepId"
                />
                <section aria-labelledby="customer-details-title"
                    class="min-w-0 rounded-xl border border-slate-200 bg-white p-5 sm:p-7 dark:border-white/10 dark:bg-gray-800"
                >
                    <h2 id="customer-details-title"
                        class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:text-gray-300"
                    >
                        Customer details
                    </h2>
                    <dl class="mt-5 grid gap-5 text-sm sm:grid-cols-2">
                        <div class="min-w-0">
                            <dt class="text-xs text-slate-500 dark:text-gray-400">Name</dt>
                            <dd class="mt-1 break-words font-medium text-slate-900 dark:text-white">
                                {{ $booking->customer_name }}</dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-xs text-slate-500 dark:text-gray-400">Email</dt>
                            <dd class="mt-1 break-all text-slate-900 dark:text-white">{{ $booking->customer_email }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-xs text-slate-500 dark:text-gray-400">Booking created</dt>
                            <dd class="mt-1 text-slate-900 dark:text-white">
                                <time
                                    datetime="{{ $booking->created_at->toIso8601String() }}">{{ $booking->created_at->format('d M Y, H:i') }}</time>
                            </dd>
                        </div>
                    </dl>
                </section>
                <x-bookings.items :items="$booking->items" :total="$booking->total()" />
            </div>

            <x-bookings.activity.history
                :activities="$activities"
                :workflow-steps="$booking->workflowSteps"
                :selected-workflow-step="$selectedWorkflowStep"
                :selected-workflow-step-id="$selectedWorkflowStepId"
            />
        </div>
    </div>
</div>
