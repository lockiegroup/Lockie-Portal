<x-layout title="Dashboard — Lockie Portal">
    <nav class="bg-slate-900 shadow-lg">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" class="h-8 w-auto">
            </a>
            <div class="flex items-center gap-4">
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.users.index') }}" class="text-slate-400 hover:text-white text-sm transition-colors">Manage Users</a>
                @endif
                <span class="text-slate-500 text-sm hidden sm:block">{{ auth()->user()->email }}</span>
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button class="text-slate-400 hover:text-white text-sm font-medium transition-colors">Sign out</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-6 py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Welcome back, {{ explode(' ', auth()->user()->name)[0] }}</h1>
            <p class="text-slate-500 mt-1">What would you like to do today?</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">

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

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col gap-4 opacity-60 cursor-not-allowed">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-amber-50 text-amber-600">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800">Health & Safety</h2>
                    <p class="text-slate-500 text-sm mt-1">Manage incidents, risk assessments, and compliance documentation.</p>
                </div>
                <span class="text-xs font-medium text-slate-400 uppercase tracking-wide mt-auto">Coming soon</span>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col gap-4 opacity-60 cursor-not-allowed">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center bg-sky-50 text-sky-600">
                    <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
                </div>
                <div>
                    <h2 class="font-semibold text-slate-800">Tasks</h2>
                    <p class="text-slate-500 text-sm mt-1">View assigned tasks, deadlines, and track completion progress.</p>
                </div>
                <span class="text-xs font-medium text-slate-400 uppercase tracking-wide mt-auto">Coming soon</span>
            </div>

        </div>

        <div class="mt-8 text-center">
            <span class="inline-block bg-slate-200 text-slate-600 text-xs font-medium px-3 py-1 rounded-full uppercase tracking-wide">
                {{ auth()->user()->role }}
            </span>
        </div>
    </main>
</x-layout>
