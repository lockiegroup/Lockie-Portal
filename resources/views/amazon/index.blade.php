<x-layout title="Amazon & Xero — Lockie Portal">

<main class="max-w-[1400px] mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div class="flex items-start justify-between flex-wrap gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 m-0">Amazon & Xero</h1>
            <p class="text-sm text-slate-500 mt-1 m-0">Settlement reconciliation and Xero posting.</p>
        </div>
        <div class="flex gap-2 flex-wrap">
            <button id="sync-btn" onclick="runSync()" class="bg-slate-800 text-white border-none rounded-lg px-4 py-2 text-sm cursor-pointer font-semibold hover:bg-slate-700">
                Sync Settlements
            </button>
            @if(!$hasXeroToken)
            <a href="{{ route('amazon.xero.connect') }}" class="bg-teal-500 text-white rounded-lg px-4 py-2 text-sm font-semibold no-underline inline-flex items-center hover:bg-teal-600">
                Connect Xero
            </a>
            @else
            <span class="bg-green-100 text-green-800 rounded-lg px-4 py-2 text-sm font-semibold">
                ✓ Xero Connected
            </span>
            <a href="{{ route('amazon.xero.connect') }}" class="bg-slate-100 text-slate-600 rounded-lg px-4 py-2 text-sm font-semibold no-underline hover:bg-slate-200">
                Reconnect Xero
            </a>
            @endif
            <a href="{{ route('amazon.profit') }}" class="bg-slate-100 text-slate-600 rounded-lg px-4 py-2 text-sm font-semibold no-underline hover:bg-slate-200">
                Profit Report →
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded-lg mb-4 text-sm">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded-lg mb-4 text-sm">
        {{ session('error') }}
    </div>
    @endif

    {{-- Summary Cards --}}
    <div class="grid gap-4 mb-6" style="grid-template-columns:repeat(auto-fit,minmax(200px,1fr));">
        <div class="bg-white rounded-xl shadow-sm px-5 py-5">
            <p class="text-[0.75rem] font-semibold text-slate-500 uppercase tracking-wide mb-2 m-0">Total Settlements</p>
            <p class="text-[1.75rem] font-bold text-slate-800 m-0">{{ number_format($totalSettlements) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm px-5 py-5">
            <p class="text-[0.75rem] font-semibold text-slate-500 uppercase tracking-wide mb-2 m-0">Pending Xero Posts</p>
            <p class="text-[1.75rem] font-bold m-0" style="color:{{ $pendingXero > 0 ? '#d97706' : '#16a34a' }};">{{ number_format($pendingXero) }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm px-5 py-5">
            <p class="text-[0.75rem] font-semibold text-slate-500 uppercase tracking-wide mb-2 m-0">Last Sync</p>
            <p class="text-base font-bold text-slate-800 m-0">{{ $lastSync ? \Carbon\Carbon::parse($lastSync)->diffForHumans() : 'Never' }}</p>
        </div>
    </div>

    {{-- Sync status message --}}
    <div id="sync-msg" style="display:none;" class="px-4 py-3 rounded-lg mb-4 text-sm"></div>

    {{-- Settlements Table --}}
    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100">
            <h2 class="text-base font-bold text-slate-800 m-0">Settlements</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="border-collapse w-full text-[0.8125rem]">
                <thead>
                    <tr class="bg-slate-50">
                        <th class="text-left px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Settlement ID</th>
                        <th class="text-left px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Period</th>
                        <th class="text-right px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Deposit</th>
                        <th class="text-center px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">CSV</th>
                        <th class="text-left px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Processed</th>
                        <th class="text-right px-4 py-2.5 font-bold text-slate-700 border-b-2 border-slate-200 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody id="settlements-body">
                    <tr><td colspan="6" class="text-center px-4 py-8 text-slate-400 text-sm">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div id="settlements-pagination" class="px-4 py-3 border-t border-slate-100 flex gap-2 flex-wrap"></div>
    </div>

</main>

<script>
const csrfToken = '{{ csrf_token() }}';

async function loadSettlements(page = 1) {
    const tbody = document.getElementById('settlements-body');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#94a3b8;">Loading…</td></tr>';

    const res  = await fetch(`{{ route('amazon.settlements') }}?page=${page}`);
    const data = await res.json();

    tbody.innerHTML = '';

    if (!data.data || data.data.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:2rem;color:#94a3b8;">No settlements found. Click "Sync Settlements" to import from Amazon.</td></tr>';
        return;
    }

    const downloaded = JSON.parse(localStorage.getItem('amazon_csv_downloaded') || '{}');

    data.data.forEach(s => {
        const isDownloaded = !!downloaded[s.settlement_id];
        const badge = isDownloaded
            ? '<span style="padding:2px 10px;border-radius:9999px;font-size:0.7rem;font-weight:700;background:#dcfce7;color:#166534;">downloaded</span>'
            : '<span style="padding:2px 10px;border-radius:9999px;font-size:0.7rem;font-weight:700;background:#f1f5f9;color:#94a3b8;">not downloaded</span>';
        tbody.innerHTML += `
        <tr style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
            <td style="padding:0.625rem 1rem;font-family:monospace;font-size:0.75rem;color:#475569;">${s.settlement_id}</td>
            <td style="padding:0.625rem 1rem;color:#334155;">${s.start_date} → ${s.end_date}</td>
            <td style="padding:0.625rem 1rem;text-align:right;font-weight:600;color:#1e293b;">£${parseFloat(s.deposit_amount).toFixed(2)}</td>
            <td style="padding:0.625rem 1rem;text-align:center;" id="badge-${s.settlement_id}">${badge}</td>
            <td style="padding:0.625rem 1rem;color:#64748b;font-size:0.75rem;">${s.processed_at ?? '—'}</td>
            <td style="padding:0.625rem 1rem;text-align:right;white-space:nowrap;">
                <a href="/amazon/settlements/${s.settlement_id}/view" style="background:#f1f5f9;color:#475569;border-radius:0.375rem;padding:3px 10px;font-size:0.75rem;font-weight:600;text-decoration:none;display:inline-block;margin-right:4px;">View</a>
                <a href="/amazon/settlements/${s.settlement_id}/csv" onclick="markDownloaded('${s.settlement_id}')" style="background:#1e293b;color:#fff;border-radius:0.375rem;padding:3px 10px;font-size:0.75rem;font-weight:600;text-decoration:none;display:inline-block;">Download CSV</a>
            </td>
        </tr>`;
    });

    const pag = document.getElementById('settlements-pagination');
    pag.innerHTML = '';
    if (data.last_page > 1) {
        for (let p = 1; p <= data.last_page; p++) {
            const active = p === data.current_page;
            pag.innerHTML += `<button onclick="loadSettlements(${p})" style="padding:4px 12px;border-radius:0.375rem;font-size:0.8rem;font-weight:600;cursor:pointer;border:none;background:${active ? '#1e293b' : '#f1f5f9'};color:${active ? '#fff' : '#475569'};">${p}</button>`;
        }
    }
}

async function runSync() {
    const btn = document.getElementById('sync-btn');
    const msg = document.getElementById('sync-msg');
    btn.disabled = true;
    btn.textContent = 'Syncing…';
    msg.style.display = 'none';

    try {
        const res  = await fetch('{{ route('amazon.sync') }}', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        });
        const data = await res.json();
        const ok   = data.success;

        msg.style.cssText = `display:block;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;background:${ok ? '#dcfce7' : '#fee2e2'};border:1px solid ${ok ? '#86efac' : '#fca5a5'};color:${ok ? '#166534' : '#991b1b'};`;
        msg.textContent   = data.message;
        if (ok) loadSettlements();
    } catch (e) {
        msg.style.cssText = 'display:block;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;';
        msg.textContent   = 'Request failed: ' + e.message;
    }

    btn.disabled    = false;
    btn.textContent = 'Sync Settlements';
}

function markDownloaded(settlementId) {
    const downloaded = JSON.parse(localStorage.getItem('amazon_csv_downloaded') || '{}');
    downloaded[settlementId] = true;
    localStorage.setItem('amazon_csv_downloaded', JSON.stringify(downloaded));
    const cell = document.getElementById('badge-' + settlementId);
    if (cell) cell.innerHTML = '<span style="padding:2px 10px;border-radius:9999px;font-size:0.7rem;font-weight:700;background:#dcfce7;color:#166534;">downloaded</span>';
}

loadSettlements();
</script>

</x-layout>
