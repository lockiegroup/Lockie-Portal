<x-layout title="Supplier Settings — Lockie Portal">

    <main class="max-w-2xl mx-auto px-6 py-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Supplier Settings</h1>
                <p class="text-slate-500 text-sm mt-1">Set default lead times (in weeks) per supplier for stock forecasting.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        @if($suppliers->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-10 text-center text-slate-400 text-sm">
                No suppliers found yet. Run a Stock Forecast sync first to populate supplier data.
            </div>
        @else
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <form action="{{ route('admin.supplier-settings.update') }}" method="POST">
                    @csrf
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 bg-slate-50">
                                <th class="text-left px-6 py-3 font-semibold text-slate-600">Supplier</th>
                                <th class="text-center px-6 py-3 font-semibold text-slate-600" style="width:160px;">Lead Time (weeks)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($suppliers as $supplier)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3 text-slate-800 font-medium">{{ $supplier['name'] }}</td>
                                <td class="px-6 py-3 text-center">
                                    <input type="number" name="lead_times[{{ $supplier['name'] }}]"
                                        value="{{ $supplier['lead_time_weeks'] }}"
                                        min="1" max="52" required
                                        style="width:72px;padding:5px 8px;border:1px solid #cbd5e1;border-radius:7px;font-size:0.875rem;text-align:center;color:#334155;">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div style="padding:14px 24px;border-top:1px solid #f1f5f9;background:#f8fafc;">
                        <button type="submit"
                            class="bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
                            Save Lead Times
                        </button>
                        <p style="display:inline;margin-left:12px;font-size:0.75rem;color:#94a3b8;">
                            Default is 4 weeks if not set. Individual products can override this in the forecast table.
                        </p>
                    </div>
                </form>
            </div>
        @endif
    </main>

</x-layout>
