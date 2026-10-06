@props(['items', 'total'])

<section aria-labelledby="items-title" {{ $attributes->class('min-w-0 rounded-xl border border-slate-200/80 bg-white p-5 sm:p-7') }}>
    <div class="mb-5 flex items-center justify-between gap-4">
        <h2 id="items-title" class="text-base font-semibold text-slate-900">Booking items</h2>
        <span class="text-xs text-slate-500">{{ $items->count() }} {{ $items->count() === 1 ? 'item' : 'items' }}</span>
    </div>
    <table class="w-full text-left text-sm">
        <thead class="border-b border-slate-100 text-xs text-slate-500">
            <tr>
                <th scope="col" class="pb-3 font-normal">Description</th>
                <th scope="col" class="hidden px-3 pb-3 text-center font-normal sm:table-cell">Qty</th>
                <th scope="col" class="hidden px-3 pb-3 text-right font-normal sm:table-cell">Unit price</th>
                <th scope="col" class="pb-3 pl-3 text-right font-normal">Amount</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach ($items as $item)
                <tr wire:key="item-{{ $item->id }}">
                    <td class="py-4 text-slate-700">{{ $item->description }}<span class="mt-1 block text-xs text-slate-500 sm:hidden">{{ $item->quantity }} × £{{ number_format($item->unit_price, 2) }}</span></td>
                    <td class="hidden px-3 py-4 text-center text-slate-500 tabular-nums sm:table-cell">{{ $item->quantity }}</td>
                    <td class="hidden whitespace-nowrap px-3 py-4 text-right text-slate-500 tabular-nums sm:table-cell">£{{ number_format($item->unit_price, 2) }}</td>
                    <td class="whitespace-nowrap py-4 pl-3 text-right text-slate-700 tabular-nums">£{{ number_format($item->quantity * $item->unit_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot class="border-t border-slate-200">
            <tr><th scope="row" class="pt-5 font-medium text-slate-700">Booking total</th><td class="hidden sm:table-cell"></td><td class="hidden sm:table-cell"></td><td class="whitespace-nowrap pt-5 pl-3 text-right text-xl font-semibold tracking-tight text-slate-900 tabular-nums">£{{ number_format($total, 2) }}</td></tr>
        </tfoot>
    </table>
</section>
