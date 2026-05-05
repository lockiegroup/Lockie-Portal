<x-layout title="Amazon & Xero — Lockie Portal">

<main style="max-width:1400px;margin:0 auto;padding:2rem 1.5rem;">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:1.5rem;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0;">Amazon & Xero</h1>
            <p style="font-size:0.875rem;color:#64748b;margin:0.25rem 0 0;">Settlement reconciliation and Xero posting.</p>
        </div>
        <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
            <button id="sync-btn" onclick="runSync()" style="background:#1e293b;color:#fff;border:none;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;cursor:pointer;font-weight:600;">
                Sync Settlements
            </button>
            @if(!$hasXeroToken)
            <a href="{{ route('amazon.xero.connect') }}" style="background:#00b9a5;color:#fff;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;text-decoration:none;display:inline-flex;align-items:center;">
                Connect Xero
            </a>
            @else
            <span style="background:#dcfce7;color:#166534;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;">
                ✓ Xero Connected
            </span>
            @endif
            <a href="{{ route('amazon.profit') }}" style="background:#f1f5f9;color:#475569;border-radius:0.5rem;padding:0.5rem 1rem;font-size:0.875rem;font-weight:600;text-decoration:none;">
                Profit Report →
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="background:#dcfce7;border:1px solid #86efac;color:#166534;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;">
        {{ session('error') }}
    </div>
    @endif

    {{-- Summary Cards --}}
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem;">
            <p style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Total Settlements</p>
            <p style="font-size:1.75rem;font-weight:700;color:#1e293b;margin:0;">{{ number_format($totalSettlements) }}</p>
        </div>
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem;">
            <p style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Pending Xero Posts</p>
            <p style="font-size:1.75rem;font-weight:700;color:{{ $pendingXero > 0 ? '#d97706' : '#16a34a' }};margin:0;">{{ number_format($pendingXero) }}</p>
        </div>
        <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);padding:1.25rem;">
            <p style="font-size:0.75rem;font-weight:600;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 0.5rem;">Last Sync</p>
            <p style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">{{ $lastSync ? \Carbon\Carbon::parse($lastSync)->diffForHumans() : 'Never' }}</p>
        </div>
    </div>

    {{-- Sync status message --}}
    <div id="sync-msg" style="display:none;padding:0.75rem 1rem;border-radius:0.5rem;margin-bottom:1rem;font-size:0.875rem;"></div>

    {{-- Settlements Table --}}
    <div style="background:#fff;border-radius:0.75rem;box-shadow:0 1px 4px rgba(0,0,0,0.07);overflow:hidden;">
        <div style="padding:1rem 1.25rem;border-bottom:1px solid #f1f5f9;">
            <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Settlements</h2>
        </div>
        <div style="overflow-x:auto;">
            <table style="border-collapse:collapse;width:100%;font-size:0.8125rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="text-align:left;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Settlement ID</th>
                        <th style="text-align:left;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Period</th>
                        <th style="text-align:right;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Deposit</th>
                        <th style="text-align:center;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Status</th>
                        <th style="text-align:left;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Processed</th>
                        <th style="text-align:right;padding:0.625rem 1rem;font-weight:700;color:#334155;border-bottom:2px solid #e2e8f0;white-space:nowrap;">Actions</th>
                    </tr>
                </thead>
                <tbody id="settlements-body">
                    <tr><td colspan="6" style="text-align:center;padding:2rem;color:#94a3b8;font-size:0.875rem;">Loading…</td></tr>
                </tbody>
            </table>
        </div>
        <div id="settlements-pagination" style="padding:0.75rem 1rem;border-top:1px solid #f1f5f9;display:flex;gap:0.5rem;flex-wrap:wrap;"></div>
    </div>

</main>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

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

    const statusColors = {
        pending:    'background:#fef9c3;color:#854d0e;',
        posted:     'background:#dbeafe;color:#1e40af;',
        reconciled: 'background:#dcfce7;color:#166534;',
    };

    data.data.forEach(s => {
        const statusStyle = statusColors[s.status] || 'background:#f1f5f9;color:#475569;';
        tbody.innerHTML += `
        <tr style="border-bottom:1px solid #f8fafc;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
            <td style="padding:0.625rem 1rem;font-family:monospace;font-size:0.75rem;color:#475569;">${s.settlement_id}</td>
            <td style="padding:0.625rem 1rem;color:#334155;">${s.start_date} → ${s.end_date}</td>
            <td style="padding:0.625rem 1rem;text-align:right;font-weight:600;color:#1e293b;">£${parseFloat(s.deposit_amount).toFixed(2)}</td>
            <td style="padding:0.625rem 1rem;text-align:center;">
                <span style="padding:2px 10px;border-radius:9999px;font-size:0.7rem;font-weight:700;${statusStyle}">${s.status}</span>
            </td>
            <td style="padding:0.625rem 1rem;color:#64748b;font-size:0.75rem;">${s.processed_at ?? '—'}</td>
            <td style="padding:0.625rem 1rem;text-align:right;">
                ${s.status === 'pending' ? `
                <button onclick="postToXero(${s.id})" style="background:#1e293b;color:#fff;border:none;border-radius:0.375rem;padding:3px 10px;font-size:0.75rem;cursor:pointer;font-weight:600;">Post to Xero</button>
                ` : ''}
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

async function postToXero(settlementId) {
    if (!confirm('Post this settlement to Xero?')) return;

    const res  = await fetch(`{{ url('amazon/xero/post') }}/${settlementId}`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
    });
    const data = await res.json();

    if (data.success) {
        alert('Posted to Xero successfully.');
        loadSettlements();
    } else {
        console.error('Xero post error:', data.message);
        const short = data.message.length > 300 ? data.message.substring(0, 300) + '…' : data.message;
        alert('Error: ' + short + '\n\n(Full error in browser console — press F12 → Console)');
    }
}

loadSettlements();
</script>

</x-layout>
