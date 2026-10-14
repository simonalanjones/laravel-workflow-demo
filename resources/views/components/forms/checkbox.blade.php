@props(['name', 'value', 'id', 'checked' => false])

<span class="group grid size-4 shrink-0 grid-cols-1">
    <input type="checkbox" name="{{ $name }}" value="{{ $value }}" id="{{ $id }}"
        @checked($checked)
        {{ $attributes->class('col-start-1 row-start-1 size-4 appearance-none rounded-sm border border-slate-300 dark:border-white/10 bg-white dark:bg-white/5 checked:border-indigo-500 checked:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-500 forced-colors:appearance-auto') }}>

    <svg viewBox="0 0 14 14" fill="none" aria-hidden="true"
        class="pointer-events-none col-start-1 row-start-1 size-3.5 self-center justify-self-center stroke-white forced-colors:hidden">
        <path d="M3 8L6 11L11 3.5" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="opacity-0 group-has-checked:opacity-100" />
    </svg>
</span>
