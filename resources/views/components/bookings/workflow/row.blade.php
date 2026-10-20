<div wire:key="workflow-{{ $workflowStep->id }}"
    class="border-b border-slate-100 dark:border-white/5 transition hover:bg-indigo-50 dark:hover:bg-indigo-500/10 last:border-0 {{ $selectedWorkflowStepId === $workflowStep->id ? 'bg-indigo-50 dark:bg-indigo-500/10' : '' }}">
    <button type="button" wire:click="selectWorkflowStep({{ $workflowStep->id }})"
        aria-pressed="{{ $selectedWorkflowStepId === $workflowStep->id ? 'true' : 'false' }}"
        class="group flex w-full cursor-pointer items-start gap-3 px-7 py-5 text-left focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-indigo-500 sm:px-9">
        <span aria-hidden="true"
            class="mt-0.5 flex size-5 shrink-0 items-center justify-center text-xs tabular-nums {{ $workflowStep->status === 'completed' ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-500 dark:text-gray-400' }}">
            @if ($workflowStep->status === 'completed')
                <x-icons.check class="size-4" />
            @elseif ($workflowStep->status === 'processing')
                <span
                    class="size-4 animate-spin rounded-full border-2 border-indigo-400/20 border-t-indigo-400 motion-reduce:animate-none"></span>
            @else
                {{ str_pad((string) $position, 2, '0', STR_PAD_LEFT) }}
            @endif
        </span>

        <span class="grid min-w-0 flex-1 gap-1.5">
            <span class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                <span
                    class="text-md font-medium text-slate-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $workflowStep->type)) }}</span>
                <span
                    class="text-sm font-medium {{ $taskTone }}">{{ ucfirst(str_replace('_', ' ', $workflowStep->status)) }}</span>
            </span>
            <span class="break-words text-xs leading-5 text-slate-700 dark:text-gray-300">{{ $taskMessage }}</span>
        </span>
    </button>

    @if ($workflowStep->status === 'failed' && $progress['failedTasks']->count() > 1)
        <div class="pr-7 pb-4 pl-15 sm:pr-9 sm:pl-17">
            <button type="button" wire:click="retryWorkflowStep({{ $workflowStep->id }})" wire:loading.attr="disabled"
                class="cursor-pointer rounded-md border border-slate-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-slate-700 dark:text-gray-300 hover:bg-slate-50 dark:hover:bg-gray-900 disabled:opacity-50">Retry
                {{ str_replace('_', ' ', $workflowStep->type) }}</button>
        </div>
    @elseif (
        $progress['failedTasks']->isNotEmpty() &&
            $workflowStep->type === 'operations_signoff' &&
            $workflowStep->status === 'awaiting_approval')
        <div class="pr-7 pb-4 pl-15 sm:pr-9 sm:pl-17">
            <button type="button" wire:click="approveWorkflowStep({{ $workflowStep->id }})" wire:loading.attr="disabled"
                class="cursor-pointer rounded-md border border-amber-400/20 bg-amber-50 dark:bg-amber-500/10 px-3 py-1.5 text-xs font-medium text-amber-700 dark:text-amber-300 hover:bg-amber-100 dark:hover:bg-amber-500/20 disabled:opacity-50">Approve
                signoff</button>
        </div>
    @endif
</div>
