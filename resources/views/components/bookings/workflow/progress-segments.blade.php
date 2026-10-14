@props(['workflows', 'completedCount'])

<nav aria-label="Workflow progress" {{ $attributes->class('flex flex-wrap items-center gap-x-4 gap-y-2') }}>
    <p class="whitespace-nowrap text-xs font-medium text-slate-500 dark:text-gray-400 tabular-nums">{{ $completedCount }}
        of {{ $workflows->count() }} complete</p>
    <ol role="list" class="flex flex-wrap items-center gap-2">
        @foreach ($workflows as $workflow)
            @php
                $dotTone = match ($workflow->status) {
                    'completed' => 'bg-emerald-600 group-hover:bg-emerald-800',
                    'failed' => 'bg-red-600 group-hover:bg-red-800',
                    'awaiting_approval' => 'bg-yellow-600 group-hover:bg-yellow-800',
                    'processing' => 'bg-blue-600 group-hover:bg-blue-800',
                    default => 'bg-slate-200 dark:bg-gray-700 group-hover:bg-slate-400 dark:group-hover:bg-gray-500',
                };
                $haloTone = match ($workflow->status) {
                    'failed' => 'bg-red-100 dark:bg-red-500/20',
                    'awaiting_approval' => 'bg-yellow-100 dark:bg-yellow-500/20',
                    'processing' => 'bg-blue-100 dark:bg-blue-500/20',
                    default => null,
                };
                $stepLabel =
                    ucfirst(str_replace('_', ' ', $workflow->type)) . ': ' . str_replace('_', ' ', $workflow->status);
            @endphp
            <li wire:key="progress-segment-{{ $workflow->id }}">
                <button type="button" wire:click="selectWorkflow({{ $workflow->id }})" title="{{ $stepLabel }}"
                    class="group relative flex size-5 cursor-pointer items-center justify-center rounded-full focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-indigo-500">
                    @if ($haloTone)
                        <span aria-hidden="true" class="absolute size-5 rounded-full {{ $haloTone }}"></span>
                    @endif
                    <span aria-hidden="true" class="relative block size-2.5 rounded-full {{ $dotTone }}"></span>
                    <span class="sr-only">{{ $stepLabel }}. View activity.</span>
                </button>
            </li>
        @endforeach
    </ol>
</nav>
