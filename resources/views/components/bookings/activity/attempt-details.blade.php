<details wire:ignore.self wire:key="attempt-details-{{ $attempt->id }}" class="mt-1 min-w-0">
    <summary
        class="cursor-pointer text-xs font-medium text-slate-700 dark:text-gray-300 focus-visible:outline-2 focus-visible:outline-indigo-500">
        View attempt details</summary>
    <div
        class="mt-2 overflow-hidden rounded-lg bg-slate-100 dark:bg-gray-950 font-mono text-xs/5 text-slate-700 dark:text-gray-200">
        <div class="grid gap-3 p-3">
            <dl class="grid grid-cols-2 gap-x-3 gap-y-2">
                <div>
                    <dt class="font-semibold text-slate-500 dark:text-gray-400">Outcome</dt>
                    <dd class="{{ $outcomeTone }}">{{ ucfirst($attempt->status) }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500 dark:text-gray-400">Duration</dt>
                    <dd>{{ $attempt->duration_ms !== null ? number_format($attempt->duration_ms) . ' ms' : 'In progress' }}
                    </dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500 dark:text-gray-400">Started</dt>
                    <dd>{{ $attempt->started_at->format('d M Y, H:i:s') }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-slate-500 dark:text-gray-400">HTTP status</dt>
                    <dd class="{{ $outcomeTone }}">{{ $attempt->http_status ?? 'Not received' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="font-semibold text-slate-500 dark:text-gray-400">Idempotency key</dt>
                    <dd class="break-all text-sky-700 dark:text-sky-300">
                        {{ $attempt->providerRequest->idempotency_key }}</dd>
                </div>
            </dl>
            @if ($attempt->status === 'unknown')
                <p class="text-amber-700 dark:text-amber-300">No response received — outcome unknown.</p>
            @elseif ($attempt->status === 'processing')
                <p class="text-sky-700 dark:text-sky-300">Awaiting a response. If processing was interrupted, the
                    outcome may be unknown.</p>
            @endif
            @if ($attempt->error_message)
                <p class="break-words text-rose-700 dark:text-rose-300">{{ $attempt->error_message }}</p>
            @endif
            @if ($attempt->response_body !== null)
                <div class="min-w-0">
                    <p class="mb-2 font-semibold text-slate-500 dark:text-gray-400">Received response</p>
                    <pre class="max-h-64 overflow-auto whitespace-pre-wrap break-all text-xs/5 text-slate-700 dark:text-gray-200"><code>{{ $responseBody === '' ? '(Empty response body)' : $responseBody }}</code></pre>
                </div>
            @endif
        </div>
    </div>
</details>
