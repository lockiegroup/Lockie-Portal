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

    <div class="bg-white rounded-xl border border-slate-200 divide-y divide-slate-100 max-w-2xl">

        {{-- Sales import section --}}
        <div class="p-6">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div>
                    <h2 class="text-base font-semibold text-slate-800">Sales Enquiry Import</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Export from <strong>Reports → Sales → Sales Enquiry</strong> in Unleashed.</p>
                    <p class="text-xs text-slate-400 mt-0.5">Required columns: Order No., Order Date, Required Date, Completed Date, Warehouse, Customer Code, Customer, Customer Type, Product Code, Product Group, Status, Quantity, Sub Total</p>
                </div>
                @if($salesFrom)
                <div class="text-right text-xs text-slate-400 shrink-0">
                    <div>Data covers: <span class="text-slate-500 font-medium">{{ $salesFrom }} – {{ $salesTo }}</span></div>
                </div>
                @endif
            </div>

            <div class="flex flex-wrap gap-2 mb-4">
                @if($doKA)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-sky-100 text-sky-700">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Updates Key Accounts sales
                </span>
                @endif
                @if($doStock)
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Updates Stock Watchlist sales
                </span>
                @endif
            </div>

            <form action="{{ route('imports.sales') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-3 flex-wrap">
                @csrf
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                    class="block text-sm text-slate-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-slate-100 file:text-slate-700 file:font-medium file:cursor-pointer hover:file:bg-slate-200 transition">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 transition shrink-0">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    Import
                </button>
            </form>

            @if($lastImport)
            @php
                $isQueued = $lastImport->action === 'imports.sales.queued';
                $isError  = $lastImport->action === 'imports.sales.error';
                $ago      = $lastImport->created_at->diffForHumans();
            @endphp
            <div class="mt-4 flex items-center gap-2 text-xs rounded-lg px-3 py-2 {{ $isError ? 'bg-red-50 text-red-700 border border-red-200' : ($isQueued ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-green-50 text-green-700 border border-green-200') }}">
                @if($isQueued)
                <svg class="animate-spin shrink-0" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                <span>Processing import… <span class="opacity-60">started {{ $ago }} — this page will refresh automatically</span></span>
                @elseif($isError)
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="shrink-0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                <span>{{ $lastImport->description }} <span class="opacity-60">{{ $ago }}</span></span>
                @else
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="shrink-0"><polyline points="20 6 9 17 4 12"/></svg>
                <span>{{ $lastImport->description }} <span class="opacity-60">{{ $ago }}</span></span>
                @endif
            </div>
            @if($isQueued)
            <script>setTimeout(() => location.reload(), 15000)</script>
            @endif
            @endif
        </div>

        {{-- Substitution rules section (stock ordering users only) --}}
        @if($doStock)
        <div class="p-6">
            <h3 class="text-sm font-semibold text-slate-700 mb-0.5">Product Code Substitution Rules</h3>
            <p class="text-xs text-slate-400 mb-4">Remap product codes before matching against the Stock Watchlist. Applied to every import.</p>

            @if($substitutions->isNotEmpty())
            <div class="mb-4 divide-y divide-slate-100 border border-slate-200 rounded-lg overflow-hidden">
                @foreach($substitutions as $sub)
                <div class="flex items-center justify-between px-3 py-2 text-sm bg-white">
                    <span class="font-mono text-slate-700 text-xs">{{ $sub->find }} <span class="text-slate-400 mx-1">→</span> {{ $sub->replace }}</span>
                    <form action="{{ route('imports.substitutions.destroy', $sub) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-600 transition text-xs font-medium">Remove</button>
                    </form>
                </div>
                @endforeach
            </div>
            @else
            <p class="text-xs text-slate-400 mb-4">No substitution rules yet.</p>
            @endif

            <form action="{{ route('imports.substitutions.store') }}" method="POST" class="flex gap-2 items-end flex-wrap">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Find</label>
                    <input type="text" name="find" placeholder="e.g. OLD" required
                        class="border border-slate-300 rounded-lg px-3 py-2 text-sm w-32 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <span class="text-slate-300 pb-2 text-lg leading-none">→</span>
                <div>
                    <label class="block text-xs font-medium text-slate-500 mb-1">Replace with</label>
                    <input type="text" name="replace" placeholder="e.g. NEW" required
                        class="border border-slate-300 rounded-lg px-3 py-2 text-sm w-32 focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-700 transition">
                    Add Rule
                </button>
            </form>
        </div>
        @endif

    </div>

</main>
</x-layout>
