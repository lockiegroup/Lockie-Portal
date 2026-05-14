<x-layout title="Dashboard — Lockie Portal">

    <main class="max-w-5xl mx-auto px-6 py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="text-slate-500 mt-1">What would you like to do today?</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

            @if(auth()->user()->hasModule('sales'))
            <a href="{{ route('sales') }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col gap-4 hover:shadow-md hover:border-slate-300 transition-all">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-emerald-50 text-emerald-600">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800">Sales Figures</h2>
                    <p class="text-slate-500 text-sm mt-1">View and track your team's sales performance, targets, and reports.</p>
                </div>
                <span class="text-xs font-medium text-emerald-600 uppercase tracking-wide mt-auto">Open &rarr;</span>
            </a>
            @endif

            @if(auth()->user()->hasModule('envelopes'))
            <a href="{{ route('church-envelopes.index') }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col gap-4 hover:shadow-md hover:border-slate-300 transition-all">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-violet-50 text-violet-600">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800">Church Envelopes</h2>
                    <p class="text-slate-500 text-sm mt-1">Generate print-ready CSV files for church envelope box sets.</p>
                </div>
                <span class="text-xs font-medium text-violet-600 uppercase tracking-wide mt-auto">Open &rarr;</span>
            </a>
            @endif

            @if(auth()->user()->hasModule('stock'))
            <a href="{{ route('stock.index') }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col gap-4 hover:shadow-md hover:border-slate-300 transition-all">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-sky-50 text-sky-600">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                        <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800">Stock Overview</h2>
                    <p class="text-slate-500 text-sm mt-1">Monitor stock values by warehouse and track trends over time.</p>
                </div>
                <span class="text-xs font-medium text-sky-600 uppercase tracking-wide mt-auto">Open &rarr;</span>
            </a>
            @endif

            @if(auth()->user()->hasModule('print_schedule'))
            <a href="{{ route('print.index') }}" class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col gap-4 hover:shadow-md hover:border-slate-300 transition-all">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-rose-50 text-rose-600">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800">Print Schedule</h2>
                    <p class="text-slate-500 text-sm mt-1">Manage A1 Printing jobs, machine queues, and lead times.</p>
                </div>
                <span class="text-xs font-medium text-rose-600 uppercase tracking-wide mt-auto">Open &rarr;</span>
            </a>
            @endif

        </div>

        <div class="mt-8 text-center">
            <span class="inline-block bg-slate-200 text-slate-600 text-xs font-medium px-3 py-1 rounded-full uppercase tracking-wide">
                {{ auth()->user()->role }}
            </span>
        </div>
    </main>
</x-layout>
