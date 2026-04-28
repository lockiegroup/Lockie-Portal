<x-layout title="Imports — Lockie Portal">
<main class="max-w-screen-xl mx-auto px-6 py-10">

    <div class="mb-8">
        <h1 class="text-2xl font-bold text-slate-800">Imports</h1>
        <p class="text-slate-500 mt-1">Upload an Unleashed Sales Enquiry export to update sales data.</p>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $errors->first() }}</div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 p-6 max-w-xl">
        <h2 class="text-base font-semibold text-slate-800 mb-1">Sales Enquiry Import</h2>
        <p class="text-sm text-slate-500 mb-1">Export from <strong>Reports → Sales → Sales Enquiry</strong> in Unleashed.</p>
        <p class="text-sm text-slate-500 mb-4">Required columns: <strong>Order Date, Customer Code, Product Code, Quantity, Sub Total</strong></p>

        <div class="mb-4 flex flex-wrap gap-2">
            @if($doKA)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-sky-100 text-sky-700">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Updates Key Accounts sales
            </span>
            @endif
            @if($doStock)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                Updates Stock Watchlist sales
            </span>
            @endif
        </div>

        <form action="{{ route('imports.sales') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                class="block w-full text-sm text-slate-600 mb-4 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-900 file:text-white file:font-medium file:cursor-pointer">
            <button type="submit"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 transition">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                Import
            </button>
        </form>
    </div>

    @if($doStock)
    <div class="bg-white rounded-xl border border-slate-200 p-6 max-w-xl mt-6">
        <h2 class="text-base font-semibold text-slate-800 mb-1">Product Code Substitution Rules</h2>
        <p class="text-sm text-slate-500 mb-4">Remap product codes before matching against the Stock Watchlist. Applied to every import.</p>

        @if($substitutions->isNotEmpty())
        <div class="mb-4 divide-y divide-slate-100 border border-slate-200 rounded-lg overflow-hidden">
            @foreach($substitutions as $sub)
            <div class="flex items-center justify-between px-3 py-2 text-sm bg-white">
                <span class="font-mono text-slate-700">{{ $sub->find }} <span class="text-slate-400 mx-1">→</span> {{ $sub->replace }}</span>
                <form action="{{ route('imports.substitutions.destroy', $sub) }}" method="POST">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-red-400 hover:text-red-600 transition text-xs font-medium">Remove</button>
                </form>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-slate-400 mb-4">No substitution rules yet.</p>
        @endif

        <form action="{{ route('imports.substitutions.store') }}" method="POST" class="flex gap-2 items-end flex-wrap">
            @csrf
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Find (product code fragment)</label>
                <input type="text" name="find" placeholder="e.g. OLD" required
                    class="border border-slate-300 rounded-lg px-3 py-2 text-sm w-36 focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-600 mb-1">Replace with</label>
                <input type="text" name="replace" placeholder="e.g. NEW" required
                    class="border border-slate-300 rounded-lg px-3 py-2 text-sm w-36 focus:outline-none focus:ring-2 focus:ring-sky-500">
            </div>
            <button type="submit"
                class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 transition">
                Add Rule
            </button>
        </form>
    </div>
    @endif

</main>
</x-layout>
