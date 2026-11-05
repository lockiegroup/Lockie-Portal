<x-layout title="Quotes">
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6 flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Quotes</h1>
            <p class="text-slate-500 mt-0.5 text-sm">All quotes raised for JW Products.</p>
        </div>
        <a href="{{ route('quotes.create') }}"
            class="px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
            + New Quote
        </a>
    </div>

    @if(session('success'))
    <div class="mb-4 px-4 py-3 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        @if($quotes->isEmpty())
        <p class="px-6 py-12 text-center text-slate-400 text-sm">No quotes yet. <a href="{{ route('quotes.create') }}" class="text-sky-600 hover:underline">Create the first one.</a></p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-[0.72rem] text-slate-500 uppercase tracking-wide">
                        <th class="text-left px-4 py-3 font-semibold">Quote No.</th>
                        <th class="text-left px-4 py-3 font-semibold">Customer</th>
                        <th class="text-left px-4 py-3 font-semibold">Reference</th>
                        <th class="text-right px-4 py-3 font-semibold">Total</th>
                        <th class="text-left px-4 py-3 font-semibold">Created By</th>
                        <th class="text-left px-4 py-3 font-semibold">Date</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @foreach($quotes as $q)
                    <tr class="hover:bg-slate-50/60 transition-colors">
                        <td class="px-4 py-3 font-mono text-sm font-semibold text-slate-800">{{ $q->quote_number }}</td>
                        <td class="px-4 py-3 text-slate-700">{{ $q->customer_name }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $q->reference ?: '—' }}</td>
                        <td class="px-4 py-3 text-right font-medium text-slate-700">
                            £{{ number_format($q->total_value, 2) }}
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $q->createdBy?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $q->created_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('quotes.show', $q) }}"
                                class="px-2.5 py-1 text-xs font-semibold text-slate-500 border border-slate-200 rounded-lg hover:bg-slate-100 transition-colors">
                                View
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($quotes->hasPages())
        <div class="px-4 py-3 border-t border-slate-100">
            {{ $quotes->links() }}
        </div>
        @endif
        @endif
    </div>

</main>
</x-layout>
