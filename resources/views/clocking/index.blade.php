<x-layout title="Clocking — Lockie Portal">
<main style="max-width:900px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="margin-bottom:1.5rem;">
        <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">Clocking</h1>
        @include('clocking._tabs', ['active' => 'dashboard'])
    </div>

    {{-- Who's In Now --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;margin-bottom:1.5rem;">
        <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0 0 1rem;">Who's In Now</h2>
        @if($inNow->isEmpty())
            <p style="color:#94a3b8;font-size:0.875rem;">Nobody is currently clocked in.</p>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0;color:#64748b;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
                    <th style="text-align:left;padding:0.5rem 0.75rem 0.5rem 0;">Name</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;">Clocked In</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;">Duration</th>
                </tr>
            </thead>
            <tbody>
            @foreach($inNow as $punch)
            @php
                $london  = new \DateTimeZone('Europe/London');
                $sinceL  = $punch->punched_at->setTimezone($london);
                $mins    = (int) $punch->punched_at->diffInMinutes(now());
                $dur     = sprintf('%dh %02dm', intdiv($mins,60), $mins % 60);
            @endphp
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:0.625rem 0.75rem 0.625rem 0;font-weight:600;color:#1e293b;">{{ $punch->employee->name }}</td>
                <td style="padding:0.625rem 0.75rem;color:#475569;">{{ $sinceL->format('H:i') }}</td>
                <td style="padding:0.625rem 0.75rem;color:#64748b;">{{ $dur }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Kiosk Devices --}}
    <div style="background:#fff;border-radius:14px;border:1px solid #e2e8f0;padding:1.5rem;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
            <h2 style="font-size:0.9375rem;font-weight:700;color:#1e293b;margin:0;">Kiosk Devices</h2>
            <button onclick="showRegister()"
                style="padding:0.4rem 0.875rem;background:#1e293b;color:#fff;border:none;border-radius:7px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
                + Register New Device
            </button>
        </div>

        <div id="register-form" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;margin-bottom:1rem;">
            <p style="font-size:0.8125rem;color:#64748b;margin-bottom:0.75rem;">Enter a name for the new device, then take the 6-digit code to the kiosk.</p>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <input type="text" id="device-name" placeholder="e.g. Main Door" maxlength="80"
                    style="flex:1;min-width:180px;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:7px;font-size:0.875rem;">
                <button onclick="generateCode()"
                    style="padding:0.5rem 1rem;background:#6366f1;color:#fff;border:none;border-radius:7px;font-size:0.875rem;font-weight:600;cursor:pointer;">
                    Generate Code
                </button>
            </div>
            <div id="register-result" style="margin-top:0.75rem;font-size:0.875rem;min-height:1.5rem;"></div>
        </div>

        @if($devices->isEmpty())
            <p style="color:#94a3b8;font-size:0.875rem;">No devices registered yet.</p>
        @else
        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
            <thead>
                <tr style="border-bottom:1px solid #e2e8f0;color:#64748b;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.05em;">
                    <th style="text-align:left;padding:0.5rem 0.75rem 0.5rem 0;">Name</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;">Last Seen</th>
                    <th style="text-align:left;padding:0.5rem 0.75rem;">Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($devices as $dev)
            <tr style="border-bottom:1px solid #f1f5f9;" id="dev-row-{{ $dev->id }}">
                <td style="padding:0.625rem 0.75rem 0.625rem 0;font-weight:600;color:#1e293b;">{{ $dev->name }}</td>
                <td style="padding:0.625rem 0.75rem;color:#475569;">
                    {{ $dev->last_seen_at ? $dev->last_seen_at->setTimezone('Europe/London')->format('d M Y H:i') : 'Never' }}
                </td>
                <td style="padding:0.625rem 0.75rem;">
                    @if($dev->is_active)
                        <span style="background:#dcfce7;color:#166534;padding:2px 8px;border-radius:99px;font-size:0.75rem;font-weight:600;">Active</span>
                    @else
                        <span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:99px;font-size:0.75rem;font-weight:600;">Revoked</span>
                    @endif
                </td>
                <td style="padding:0.625rem 0;text-align:right;">
                    @if($dev->is_active)
                    <button onclick="revokeDevice({{ $dev->id }})"
                        style="padding:3px 10px;background:none;border:1px solid #fecaca;border-radius:6px;color:#ef4444;font-size:0.75rem;cursor:pointer;">
                        Revoke
                    </button>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif
    </div>

</main>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function showRegister() {
    const f = document.getElementById('register-form');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
}

async function generateCode() {
    const name = document.getElementById('device-name').value.trim();
    const res  = document.getElementById('register-result');
    if (!name) { res.innerHTML = '<span style="color:#ef4444">Enter a device name.</span>'; return; }
    res.textContent = 'Generating…';
    try {
        const r    = await fetch('{{ route('clocking.devices.generate') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify({ name }),
        });
        const json = await r.json();
        if (r.ok) {
            res.innerHTML = `Code: <strong style="font-size:1.5rem;letter-spacing:0.2em;">${json.code}</strong> — valid 10 minutes. Take this to the kiosk.`;
        } else {
            res.innerHTML = '<span style="color:#ef4444">' + (json.message || 'Error') + '</span>';
        }
    } catch(e) { res.innerHTML = '<span style="color:#ef4444">Network error.</span>'; }
}

async function revokeDevice(id) {
    if (!confirm('Revoke this device? The kiosk will need to be re-registered.')) return;
    const r = await fetch(`/clocking/devices/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    });
    if (r.ok) {
        const row = document.getElementById('dev-row-' + id);
        if (row) row.querySelector('td:nth-child(3)').innerHTML =
            '<span style="background:#fee2e2;color:#991b1b;padding:2px 8px;border-radius:99px;font-size:0.75rem;font-weight:600;">Revoked</span>';
    }
}
</script>
</x-layout>
