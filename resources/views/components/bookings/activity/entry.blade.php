<li wire:key="activity-{{ $activity->id }}" class="relative flex gap-x-4 pb-6 last:pb-0">
    @if (!$isLast)
        <span aria-hidden="true" class="activity-connector absolute top-0 bottom-0 left-0 flex w-6 justify-center"><span
                class="w-px bg-slate-200 dark:bg-white/15"></span></span>
    @endif
    <span aria-hidden="true"
        class="relative flex size-6 shrink-0 items-center justify-center bg-slate-50 dark:bg-gray-900">
        @if ($isSuccess)
            <x-icons.check-circle class="size-6 text-indigo-500" />
        @elseif ($isFailure)
            <x-icons.exclamation-triangle class="size-5 text-red-600 dark:text-red-400" />
        @else
            <span
                class="size-1.5 rounded-full bg-slate-100 dark:bg-white/10 ring ring-slate-300 dark:ring-white/20"></span>
        @endif
    </span>
    <div class="min-w-0 flex-1 py-0.5">
        <div class="flex items-start justify-between gap-x-4">
            <p
                class="min-w-0 break-words text-xs/5 {{ $isFailure ? 'font-medium text-red-700 dark:text-red-300' : ($isSuccess ? 'font-medium text-slate-900 dark:text-white' : 'text-slate-500 dark:text-gray-400') }}">
                {{ $activity->message }}</p>
            <time class="shrink-0 text-right text-xs/5 text-slate-500 dark:text-gray-400 tabular-nums"
                title="{{ $activity->created_at->format('d M Y, H:i:s') }}"
                aria-label="{{ $activity->created_at->format('d M Y, H:i:s') }}"
                datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('H:i:s') }}</time>
        </div>
        <p x-show="showEventTypes" style="display: none;"
            class="mt-1 break-all font-mono text-[11px] text-slate-500 dark:text-gray-400">
            {{ $activity->type }}</p>
        @if ($activity->providerAttempt)
            <x-bookings.activity.attempt-details :attempt="$activity->providerAttempt" />
        @endif
    </div>
</li>
