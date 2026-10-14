@props(['workflows', 'progress', 'selectedWorkflowId' => null])

@php
    $awaitingApproval = $progress['failedTasks']->isEmpty() && $progress['approvalTask'] !== null;
@endphp

<section aria-labelledby="workflows-title"
    {{ $attributes->class('overflow-hidden rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-gray-800') }}>
    <div class="p-5 sm:p-7">
        <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
            <h2 id="workflows-title"
                class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:text-gray-300">Workflow
                progress</h2>
            <x-bookings.workflow.progress-segments :workflows="$workflows" :completed-count="$progress['completedCount']" />
        </div>

        @if ($progress['isComplete'])
            <div class="rounded-md bg-green-50 dark:bg-green-500/10 p-4 outline outline-green-500/20">
                <div class="flex">
                    <div class="shrink-0">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                            class="size-5 text-green-600 dark:text-green-400">
                            <path
                                d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z"
                                clip-rule="evenodd" fill-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">
                            {{ $progress['summaryTitle'] }}</h3>
                        <div class="mt-2 text-sm text-green-700 dark:text-green-200/85">
                            <p>{{ $progress['summaryDescription'] }}</p>
                        </div>
                    </div>
                </div>
            </div>
        @elseif ($progress['failedTasks']->isNotEmpty())
            <div class="rounded-md bg-red-50 dark:bg-red-500/15 p-4 outline outline-red-500/25">
                <div class="flex">
                    <div class="shrink-0">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                            class="size-5 text-red-600 dark:text-red-400">
                            <path
                                d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16ZM8.28 7.22a.75.75 0 0 0-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 1 0 1.06 1.06L10 11.06l1.72 1.72a.75.75 0 1 0 1.06-1.06L11.06 10l1.72-1.72a.75.75 0 0 0-1.06-1.06L10 8.94 8.28 7.22Z"
                                clip-rule="evenodd" fill-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 min-w-0">
                        <h3 class="text-sm font-medium text-red-800 dark:text-red-200">{{ $progress['summaryTitle'] }}
                        </h3>
                        <div class="mt-2 text-sm text-red-700 dark:text-red-200/80">
                            <ul role="list" class="list-disc space-y-1 pl-5">
                                @foreach ($progress['failedTasks'] as $failedTask)
                                    <li class="break-words"><span
                                            class="font-medium">{{ ucfirst(str_replace('_', ' ', $failedTask->type)) }}:</span>
                                        {{ $failedTask->message }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @if ($progress['failedTasks']->count() === 1)
                            <x-forms.button variant="danger"
                                wire:click="retryWorkflow({{ $progress['failedTasks']->first()->id }})"
                                wire:loading.attr="disabled" class="mt-4">
                                Retry {{ str_replace('_', ' ', $progress['failedTasks']->first()->type) }}
                            </x-forms.button>
                        @else
                            <a href="#workflow-tasks"
                                class="mt-4 inline-flex rounded-md bg-red-700 px-3 py-2 text-sm font-medium text-white hover:bg-red-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-600">Review
                                failed steps</a>
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($awaitingApproval)
            <div class="rounded-md bg-yellow-50 dark:bg-yellow-500/10 p-4 outline outline-yellow-500/15">
                <div class="flex">
                    <div class="shrink-0">
                        <svg viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"
                            class="size-5 text-yellow-700 dark:text-yellow-300">
                            <path
                                d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495ZM10 5a.75.75 0 0 1 .75.75v3.5a.75.75 0 0 1-1.5 0v-3.5A.75.75 0 0 1 10 5Zm0 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z"
                                clip-rule="evenodd" fill-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-100">
                            {{ $progress['summaryTitle'] }}</h3>
                        <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-100/80">
                            <p>{{ $progress['summaryDescription'] }}</p>
                        </div>
                        <x-forms.button variant="warning"
                            wire:click="approveWorkflow({{ $progress['approvalTask']->id }})"
                            wire:loading.attr="disabled" class="mt-4">
                            Approve signoff
                        </x-forms.button>

                    </div>
                </div>
            </div>
        @else
            <div class="rounded-lg border border-slate-100 dark:border-white/5 bg-slate-50 dark:bg-white/5 p-5">
                @if ($progress['summaryLabel'] !== '')
                    <p class="mb-2 text-xs font-medium text-slate-500 dark:text-gray-400">
                        {{ $progress['summaryLabel'] }}</p>
                @endif
                <h3 class="text-2xl font-semibold tracking-tight text-slate-900 dark:text-white">
                    {{ $progress['summaryTitle'] }}</h3>
                <p class="mt-2 max-w-lg text-sm leading-6 text-slate-500 dark:text-gray-400">
                    {{ $progress['summaryDescription'] }}</p>
            </div>
        @endif
    </div>

    <div id="workflow-tasks" class="scroll-mt-6 border-t border-slate-100 dark:border-white/5">
        @forelse ($workflows as $workflow)
            <x-bookings.workflow.row :workflow="$workflow" :progress="$progress" :position="$loop->iteration" :selected-workflow-id="$selectedWorkflowId" />
        @empty
            <p class="px-7 py-6 text-sm text-slate-500 dark:text-gray-400 sm:px-9">No workflows have been created for
                this booking.</p>
        @endforelse
    </div>
    @if ($progress['taskCount'] > 0)
        <p class="border-t border-slate-100 dark:border-white/5 px-7 py-3 text-xs text-slate-500 dark:text-gray-400">
            Select a step to view its activity.</p>
    @endif
</section>
