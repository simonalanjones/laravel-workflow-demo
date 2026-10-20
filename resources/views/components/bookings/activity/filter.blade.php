@props(['workflowSteps', 'selectedWorkflowStep' => null, 'selectedWorkflowStepId' => null])

<div class="grid w-60 max-w-full gap-2">
    <label class="grid min-w-0 grid-cols-1">
        <span class="sr-only">Filter processing history by workflow step</span>
        <select
            x-on:change="$event.target.value === '' ? $wire.showAllActivity() : $wire.selectWorkflowStep(Number($event.target.value))"
            class="col-start-1 row-start-1 w-full min-w-0 cursor-pointer appearance-none rounded-md bg-white dark:bg-white/5 py-1.5 pr-8 pl-3 text-base text-slate-900 dark:text-white outline-1 -outline-offset-1 outline-slate-300 dark:outline-white/10 *:bg-white dark:*:bg-gray-800 focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-indigo-500 sm:text-sm/6">
            <option value="" @selected($selectedWorkflowStepId === null)>All events</option>
            @foreach ($workflowSteps as $workflowStep)
                <option value="{{ $workflowStep->id }}" @selected($selectedWorkflowStepId === $workflowStep->id)>
                    {{ ucfirst(str_replace('_', ' ', $workflowStep->type)) }}</option>
            @endforeach
        </select>
        <svg viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"
            class="pointer-events-none col-start-1 row-start-1 mr-2 size-5 self-center justify-self-end text-slate-500 dark:text-gray-400 sm:size-4">
            <path
                d="M4.22 6.22a.75.75 0 0 1 1.06 0L8 8.94l2.72-2.72a.75.75 0 1 1 1.06 1.06l-3.25 3.25a.75.75 0 0 1-1.06 0L4.22 7.28a.75.75 0 0 1 0-1.06Z"
                clip-rule="evenodd" fill-rule="evenodd" />
        </svg>
    </label>
    @if ($selectedWorkflowStep)
        <button type="button" wire:click="showAllActivity"
            class="justify-self-end cursor-pointer rounded-sm text-xs font-medium text-indigo-600 hover:underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 dark:text-indigo-400">
            Show all
        </button>
    @endif
</div>
