@props(['workflows', 'progress', 'selectedWorkflowId' => null])

<section aria-labelledby="workflows-title" {{ $attributes->class('overflow-hidden rounded-xl border border-slate-200/80 bg-white') }}>
    <div class="p-5 sm:p-7">
        <div class="mb-5 flex items-center justify-between gap-4">
            <h2 id="workflows-title" class="text-base font-semibold text-slate-900">Workflow progress</h2>
            <span class="text-xs text-slate-500 tabular-nums">{{ $progress['completedCount'] }} of {{ $progress['taskCount'] }} complete</span>
        </div>

        <div class="rounded-lg border p-5 {{ $progress['failedTasks']->isNotEmpty() ? 'border-red-100 bg-red-50/40' : 'border-slate-100 bg-slate-50/80' }}">
            <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.12em] {{ $progress['failedTasks']->isNotEmpty() ? 'text-red-700' : 'text-slate-500' }}">{{ $progress['summaryLabel'] }}</p>
            <h3 class="text-xl font-semibold tracking-tight text-slate-900">{{ $progress['summaryTitle'] }}</h3>
            <p class="mt-2 max-w-lg text-sm leading-6 text-slate-500">{{ $progress['summaryDescription'] }}</p>
            @if ($progress['failedTasks']->count() === 1)
                <button type="button" wire:click="retryWorkflow({{ $progress['failedTasks']->first()->id }})" wire:loading.attr="disabled" class="mt-4 cursor-pointer rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900 disabled:opacity-50">Retry {{ str_replace('_', ' ', $progress['failedTasks']->first()->type) }}</button>
            @elseif ($progress['failedTasks']->count() > 1)
                <a href="#workflow-tasks" class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-slate-700">Review failed steps</a>
            @elseif ($progress['approvalTask'])
                <button type="button" wire:click="approveWorkflow({{ $progress['approvalTask']->id }})" wire:loading.attr="disabled" class="mt-4 cursor-pointer rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-700 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-slate-900 disabled:opacity-50">Approve signoff</button>
            @endif
        </div>
    </div>

    <div id="workflow-tasks" class="scroll-mt-6 border-t border-slate-100 px-5 sm:px-7">
        @forelse ($workflows as $workflow)
            @php
                $taskTone = match ($workflow->status) {
                    'completed' => 'text-emerald-700', 'failed' => 'text-red-600',
                    'awaiting_approval' => 'text-amber-700', 'processing' => 'text-blue-600',
                    default => 'text-slate-500',
                };
            @endphp
            <div wire:key="workflow-{{ $workflow->id }}" class="border-b border-slate-100 last:border-0">
                <button type="button" wire:click="selectWorkflow({{ $workflow->id }})" aria-pressed="{{ $selectedWorkflowId === $workflow->id ? 'true' : 'false' }}" class="group my-1 flex w-full cursor-pointer items-start gap-3 rounded-lg px-2 py-5 text-left transition hover:bg-slate-50 focus-visible:outline-2 focus-visible:outline-blue-500 {{ $selectedWorkflowId === $workflow->id ? 'bg-blue-50/50' : '' }}">
                    <span aria-hidden="true" class="mt-0.5 flex size-5 shrink-0 items-center justify-center text-xs tabular-nums {{ $workflow->status === 'completed' ? 'text-emerald-600' : 'text-slate-500' }}">
                        @if ($workflow->status === 'completed')
                            @include('partials.workflow-icon', ['icon' => 'check', 'iconClass' => 'size-4'])
                        @else
                            {{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}
                        @endif
                    </span>
                    <span class="grid min-w-0 flex-1 gap-1.5">
                        <span class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1">
                            <span class="text-sm font-medium text-slate-800">{{ ucfirst(str_replace('_', ' ', $workflow->type)) }}</span>
                            <span class="text-xs {{ $taskTone }}">{{ ucfirst(str_replace('_', ' ', $workflow->status)) }}</span>
                        </span>
                        <span class="break-words text-xs leading-5 text-slate-500">{{ $workflow->message }}</span>
                    </span>
                </button>
                @if ($workflow->status === 'failed' && $progress['failedTasks']->count() > 1)
                    <div class="pb-4 pl-10"><button type="button" wire:click="retryWorkflow({{ $workflow->id }})" wire:loading.attr="disabled" class="cursor-pointer rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">Retry {{ str_replace('_', ' ', $workflow->type) }}</button></div>
                @elseif ($progress['failedTasks']->isNotEmpty() && $workflow->type === 'operations_signoff' && $workflow->status === 'awaiting_approval')
                    <div class="pb-4 pl-10"><button type="button" wire:click="approveWorkflow({{ $workflow->id }})" wire:loading.attr="disabled" class="cursor-pointer rounded-md border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-50">Approve signoff</button></div>
                @endif
            </div>
        @empty
            <p class="py-6 text-sm text-slate-500">No workflows have been created for this booking.</p>
        @endforelse
    </div>
    @if ($progress['taskCount'] > 0)
        <p class="border-t border-slate-100 px-7 py-3 text-xs text-slate-500">Select a step to view its activity.</p>
    @endif
</section>
