<x-layout title="Print Schedule Settings — Lockie Portal">

    <nav class="bg-slate-900 shadow-lg">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" class="h-12 w-auto">
            </a>
            <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white text-sm transition-colors">&#8592; Dashboard</a>
        </div>
    </nav>

    <main class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

        <div style="margin-bottom:1.5rem;">
            <a href="{{ route('print.index') }}" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">&#8592; Print Schedule</a>
        </div>

        <h1 class="text-2xl font-bold text-slate-800 mb-6">Print Schedule Settings</h1>

        @if(session('success'))
            <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:1.5rem;color:#15803d;font-size:0.875rem;">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:0.75rem;padding:0.75rem 1rem;margin-bottom:1.5rem;color:#dc2626;font-size:0.875rem;">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <p class="text-xs text-slate-400 -mt-4 mb-2">Working schedule: Mon–Thu 08:00–16:30 (30 min break), Fri 08:00–13:30 (30 min break).</p>

        <form method="POST" action="{{ route('admin.print-settings.update') }}" class="space-y-6">
            @csrf

            {{-- Machine Throughput --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div>
                    <h2 class="font-semibold text-slate-800">Machine Throughput</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Packs completed per working day per machine. Used to estimate whether jobs will be on time.</p>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    @foreach(['auto_1' => 'Auto 1', 'auto_2' => 'Auto 2', 'auto_3' => 'Auto 3', 'baby' => 'Baby'] as $key => $label)
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ $label }}</label>
                            <div style="display:flex;align-items:center;gap:8px;">
                                <input type="number" name="throughput_{{ $key }}"
                                    value="{{ $settings['throughput_' . $key] }}" min="1"
                                    class="flex-1 px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-rose-500 focus:border-transparent transition">
                                <span class="text-xs text-slate-400 whitespace-nowrap">packs / day</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Dashboard Notes --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div style="margin-bottom:1rem;">
                    <h2 class="font-semibold text-slate-800">Schedule Overview — Manual Notes</h2>
                    <p class="text-xs text-slate-400 mt-0.5">These notes appear on the Schedule Overview page below the machine breakdown. Use it for staff availability, upcoming maintenance, holidays, or other relevant information. Plain text; line breaks are preserved.</p>
                </div>
                <textarea name="dashboard_notes" rows="8"
                    placeholder="e.g. Bank holiday Monday 26th — no production&#10;Auto 3 service scheduled Friday 30th&#10;New stock arriving Tuesday"
                    style="width:100%;padding:10px 12px;border:1px solid #cbd5e1;border-radius:0.5rem;font-size:0.875rem;color:#1e293b;font-family:inherit;resize:vertical;box-sizing:border-box;outline:none;"
                    onfocus="this.style.borderColor='#e11d48';this.style.boxShadow='0 0 0 3px rgba(225,29,72,0.1)'"
                    onblur="this.style.borderColor='#cbd5e1';this.style.boxShadow='none'"
                >{{ old('dashboard_notes', $settings['dashboard_notes']) }}</textarea>
            </div>

            <button type="submit"
                style="background:#1e293b;color:#fff;font-size:0.875rem;padding:10px 24px;border-radius:8px;border:none;cursor:pointer;font-weight:500;">
                Save Settings
            </button>
        </form>

    </main>


</x-layout>
