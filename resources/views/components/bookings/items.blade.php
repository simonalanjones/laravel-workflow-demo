@props(['items', 'total'])

<section aria-labelledby="items-title"
    {{ $attributes->class('min-w-0 rounded-xl border border-slate-200 dark:border-white/10 bg-white dark:bg-gray-800 p-5 sm:p-7') }}>
    <div class="mb-5 flex items-center justify-between gap-4">
        <h2 id="items-title" class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-700 dark:text-gray-300">
            Booking items</h2>
    </div>
    <table class="w-full divide-y divide-slate-200 dark:divide-white/15 text-left text-sm">
        <thead class="text-sm text-slate-900 dark:text-white">
            <tr>
                <th scope="col" class="py-3.5 font-semibold">Description</th>
                <th scope="col" class="hidden px-3 py-3.5 text-center font-semibold sm:table-cell">Qty</th>
                <th scope="col" class="hidden px-3 py-3.5 text-right font-semibold sm:table-cell">Unit price</th>
                <th scope="col" class="py-3.5 pl-3 text-right font-semibold">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 dark:divide-white/10">
            @foreach ($items as $item)
                <tr wire:key="item-{{ $item->id }}">
                    <td class="py-4 pr-3 font-medium text-slate-900 dark:text-white">{{ $item->description }}<span
                            class="mt-1 block text-xs font-normal text-slate-500 dark:text-gray-400 sm:hidden">{{ $item->quantity }}
                            × £{{ number_format($item->unit_price, 2) }}</span></td>
                    <td
                        class="hidden px-3 py-4 text-center text-slate-500 dark:text-gray-400 tabular-nums sm:table-cell">
                        {{ $item->quantity }}</td>
                    <td
                        class="hidden whitespace-nowrap px-3 py-4 text-right text-slate-500 dark:text-gray-400 tabular-nums sm:table-cell">
                        £{{ number_format($item->unit_price, 2) }}</td>
                    <td class="whitespace-nowrap py-4 pl-3 text-right text-slate-500 dark:text-gray-400 tabular-nums">
                        £{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="border-t border-slate-200 dark:border-white/15">
            <tr>
                <th scope="row" class="pt-5 font-semibold text-slate-900 dark:text-white">Booking total</th>
                <td class="hidden sm:table-cell"></td>
                <td class="hidden sm:table-cell"></td>
                <td
                    class="whitespace-nowrap pt-5 pl-3 text-right text-xl font-semibold tracking-tight text-slate-900 dark:text-white tabular-nums">
                    £{{ number_format($total, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</section>
