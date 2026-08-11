<x-layout title="Print Schedule Settings — Lockie Portal">


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
                    <p class="text-xs text-slate-400 mt-0.5">Packs completed per working day, by machine group and product size. Product size is detected from the product code (200 / 300 / 370). Used to estimate whether jobs will be on time and to show live rate % on tablets.</p>
                </div>

                <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:8px 12px 8px 0;color:#64748b;font-weight:600;border-bottom:1px solid #e2e8f0;"></th>
                            <th style="text-align:center;padding:8px 12px;color:#64748b;font-weight:600;border-bottom:1px solid #e2e8f0;">200mm</th>
                            <th style="text-align:center;padding:8px 12px;color:#64748b;font-weight:600;border-bottom:1px solid #e2e8f0;">300mm</th>
                            <th style="text-align:center;padding:8px 12px;color:#64748b;font-weight:600;border-bottom:1px solid #e2e8f0;">370mm</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(['auto' => 'Auto (1, 2, 3)', 'baby' => 'Baby'] as $group => $label)
                            <tr>
                                <td style="padding:10px 12px 10px 0;font-weight:600;color:#1e293b;white-space:nowrap;">{{ $label }}</td>
                                @foreach([200, 300, 370] as $size)
                                    <td style="padding:10px 6px;text-align:center;">
                                        <div style="display:flex;align-items:center;gap:6px;justify-content:center;">
                                            <input type="number" name="throughput_{{ $group }}_{{ $size }}"
                                                value="{{ $settings['throughput_' . $group . '_' . $size] }}" min="1"
                                                style="width:90px;padding:8px 10px;border:1px solid #cbd5e1;border-radius:8px;font-size:0.875rem;color:#1e293b;text-align:center;outline:none;"
                                                onfocus="this.style.borderColor='#e11d48';this.style.boxShadow='0 0 0 3px rgba(225,29,72,0.1)'"
                                                onblur="this.style.borderColor='#cbd5e1';this.style.boxShadow='none'">
                                            <span style="font-size:0.7rem;color:#94a3b8;white-space:nowrap;">/day</span>
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
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
