<x-layout title="Quote {{ $quote->quote_number }}">
<main class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $quote->quote_number }}</h1>
            <p class="text-slate-500 mt-0.5 text-sm">{{ $quote->customer_name }} &middot; {{ $quote->created_at->format('d M Y') }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('quotes.edit', $quote) }}"
                class="px-4 py-2 border border-slate-200 text-slate-700 text-sm font-semibold rounded-lg hover:bg-slate-50 transition-colors">
                Edit
            </a>
            <a href="{{ route('quotes.print', $quote) }}" target="_blank"
                class="px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Print / PDF
            </a>
            <a href="{{ route('quotes.index') }}" class="text-sm text-slate-500 hover:text-slate-800 transition-colors">← Back</a>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    {{-- Details --}}
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Customer</p>
                <p class="text-slate-800">{{ $quote->customer_name }}</p>
            </div>
            @if($quote->customer_email)
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Email</p>
                <p class="text-slate-800">{{ $quote->customer_email }}</p>
            </div>
            @endif
            @if($quote->reference)
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Reference</p>
                <p class="text-slate-800">{{ $quote->reference }}</p>
            </div>
            @endif
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Created By</p>
                <p class="text-slate-800">{{ $quote->createdBy?->name ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-0.5">Date</p>
                <p class="text-slate-800">{{ $quote->created_at->format('d M Y') }}</p>
            </div>
        </div>
    </div>

    {{-- Lines --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden mb-5">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[0.72rem] text-slate-500 uppercase tracking-wide">
                    <th class="text-left px-4 py-3 font-semibold">Description</th>
                    <th class="text-left px-4 py-3 font-semibold">Unit</th>
                    <th class="text-right px-4 py-3 font-semibold">Qty</th>
                    <th class="text-right px-4 py-3 font-semibold">Unit Price</th>
                    <th class="text-right px-4 py-3 font-semibold">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($quote->lines as $line)
                <tr>
                    <td class="px-4 py-3 text-slate-700">
                        {{ $line->description }}
                        @if($line->breaks->isNotEmpty())
                        <div class="mt-1.5 space-y-0.5">
                            @foreach($line->breaks as $brk)
                            <div class="text-xs text-slate-400">
                                From {{ rtrim(rtrim(number_format($brk->min_qty, 2), '0'), '.') }} units → £{{ number_format($brk->unit_price, 2) }} each
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-500 text-sm">{{ $line->unit ?: '—' }}</td>
                    <td class="px-4 py-3 text-right text-slate-600">{{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }}</td>
                    <td class="px-4 py-3 text-right text-slate-600">£{{ number_format($line->unit_price, 2) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-slate-800">£{{ number_format($line->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-4 py-3">
            @php $vat = $quote->total_value * 0.2; @endphp
            <div class="flex justify-end">
                <div class="w-56 space-y-1.5 text-sm">
                    <div class="flex justify-between text-slate-600">
                        <span>Subtotal (ex VAT)</span>
                        <span>£{{ number_format($quote->total_value, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600">
                        <span>VAT (20%)</span>
                        <span>£{{ number_format($vat, 2) }}</span>
                    </div>
                    <div class="flex justify-between font-bold text-slate-800 border-t border-slate-100 pt-1.5">
                        <span>Total (inc VAT)</span>
                        <span>£{{ number_format($quote->total_value + $vat, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($quote->notes)
    <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Notes</p>
        <p class="text-sm text-slate-600 whitespace-pre-line">{{ $quote->notes }}</p>
    </div>
    @endif

    <div class="flex justify-end">
        <form method="POST" action="{{ route('quotes.destroy', $quote) }}"
            onsubmit="return confirm('Delete this quote? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="text-xs text-red-400 hover:text-red-600 transition-colors">Delete quote</button>
        </form>
    </div>

</main>
</x-layout>
