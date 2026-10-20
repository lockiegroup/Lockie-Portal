<x-layout title="Clocking — Lockie Portal">
<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-800 mb-4">Clocking</h1>
        @include('clocking._tabs', ['active' => 'dashboard'])
    </div>

    {{-- Who's In Now --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-4">Who's In Now</h2>
        @if($inNow->isEmpty())
            <p class="text-slate-400 text-sm">Nobody is currently clocked in.</p>
        @else
        <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th class="text-left py-2 pr-3 text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">Name</th>
                    <th class="text-left py-2 px-3 text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">Clocked In</th>
                    <th class="text-left py-2 px-3 text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">Duration</th>
                </tr>
            </thead>
            <tbody>
            @foreach($inNow as $punch)
            @php
                $london      = new \DateTimeZone('Europe/London');
                $sinceL      = $punch->punched_at->setTimezone($london);
                $mins        = (int) $punch->punched_at->diffInMinutes(now());
                $dur         = sprintf('%dh %02dm', intdiv($mins,60), $mins % 60);
                $empSched    = ($punch->employee->schedule ?? [])[$dayKey] ?? [];
                $lateBy      = null;
                if (!empty($empSched['start'])) {
                    $schedStart = \Carbon\Carbon::createFromFormat('H:i', $empSched['start'], config('app.timezone'))->setDateFrom($punch->punched_at);
                    $lateBy = max(0, (int) floor(($punch->punched_at->timestamp - $schedStart->timestamp) / 60));
                    if ($lateBy === 0) $lateBy = null;
                }
            @endphp
            <tr class="border-b border-slate-50">
                <td class="py-2.5 pr-3 font-semibold text-slate-800">{{ $punch->employee->name }}</td>
                <td class="py-2.5 px-3 text-slate-600">
                    {{ $sinceL->format('H:i') }}
                    @if($lateBy)
                        <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded-full text-[0.7rem] font-semibold bg-red-100 text-red-700 whitespace-nowrap">{{ $lateBy }}m late</span>
                    @endif
                </td>
                <td class="py-2.5 px-3 text-slate-500">{{ $dur }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

    {{-- Scheduled today — not yet clocked in --}}
    @if($notClockedIn->isNotEmpty())
    <div class="bg-white rounded-xl border border-orange-200 p-6 mb-6">
        <h2 class="text-[0.9375rem] font-bold text-amber-800 mb-4">
            Not Clocked In Yet
            <span class="text-xs font-medium bg-orange-50 text-orange-700 px-2 py-0.5 rounded-full border border-orange-200 ml-2">{{ $notClockedIn->count() }}</span>
        </h2>
        <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th class="text-left py-2 pr-3 text-xs text-amber-800 uppercase tracking-wide border-b border-orange-200">Name</th>
                    <th class="text-left py-2 px-3 text-xs text-amber-800 uppercase tracking-wide border-b border-orange-200">Category</th>
                    <th class="text-left py-2 pr-0 pl-3 text-xs text-amber-800 uppercase tracking-wide border-b border-orange-200">Scheduled Start</th>
                </tr>
            </thead>
            <tbody>
            @foreach($notClockedIn as $emp)
            @php $start = $emp->schedule[$dayKey]['start'] ?? '—'; @endphp
            <tr class="border-b border-orange-50">
                <td class="py-2.5 pr-3 font-semibold text-slate-800">{{ $emp->name }}</td>
                <td class="py-2.5 px-3 text-slate-500">
                    @if($emp->category)
                        <span class="inline-flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full flex-shrink-0" style="background:{{ $emp->category->color }}"></span>
                            {{ $emp->category->name }}
                        </span>
                    @else
                        <span class="text-slate-300">—</span>
                    @endif
                </td>
                <td class="py-2.5 pl-3 text-amber-800 font-semibold">
                    @php
                        $scheduledStart = \Carbon\Carbon::createFromFormat('H:i', $start, config('app.timezone'))->setDateFrom($now);
                        $lateBy = max(0, (int) floor(($now->timestamp - $scheduledStart->timestamp) / 60));
                    @endphp
                    {{ $start }}
                    @if($lateBy > 0)
                        <span class="text-[0.7rem] text-red-500 ml-1">{{ $lateBy }}m late</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
    </div>
    @endif

    {{-- Recent Alerts --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="text-[0.9375rem] font-bold text-slate-800 mb-4">Recent Alerts</h2>
        @if($recentAlerts->isEmpty())
            <p class="text-slate-400 text-sm">No alerts have been sent yet.</p>
        @else
        <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th class="text-left py-2 pr-3 text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">Employee</th>
                    <th class="text-left py-2 px-3 text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">Type</th>
                    <th class="text-left py-2 px-3 text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">Date</th>
                    <th class="text-left py-2 pl-3 text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">Sent</th>
                </tr>
            </thead>
            <tbody>
            @foreach($recentAlerts as $log)
            <tr class="border-b border-slate-50">
                <td class="py-2.5 pr-3 font-semibold text-slate-800">{{ $log->employee?->name ?? '—' }}</td>
                <td class="py-2.5 px-3">
                    @if($log->alert_type === 'clockout')
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[0.7rem] font-semibold bg-amber-100 text-amber-800">Didn't clock out</span>
                    @else
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[0.7rem] font-semibold bg-red-100 text-red-700">Didn't clock in</span>
                    @endif
                </td>
                <td class="py-2.5 px-3 text-slate-600">{{ \Carbon\Carbon::parse($log->alert_date)->format('d M Y') }}</td>
                <td class="py-2.5 pl-3 text-slate-500">{{ $log->sent_at->setTimezone(config('app.timezone'))->format('H:i') }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>

    {{-- Kiosk Devices (admin only) --}}
    @if($isAdmin)
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
            <h2 class="text-[0.9375rem] font-bold text-slate-800">Kiosk Devices</h2>
            <button onclick="showRegister()"
                class="px-3.5 py-2 bg-slate-900 text-white text-sm font-semibold rounded-lg hover:bg-slate-700 transition-colors cursor-pointer border-0">
                + Register New Device
            </button>
        </div>

        <div id="register-form" class="hidden bg-slate-50 border border-slate-200 rounded-lg p-4 mb-4">
            <p class="text-[0.8125rem] text-slate-500 mb-3">Enter a name for the new device, then take the 6-digit code to the kiosk.</p>
            <div class="flex gap-2 flex-wrap">
                <input type="text" id="device-name" placeholder="e.g. Main Door" maxlength="80"
                    class="flex-1 min-w-[180px] px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                <button onclick="generateCode()"
                    class="px-4 py-2 bg-indigo-500 text-white text-sm font-semibold rounded-lg hover:bg-indigo-600 transition-colors cursor-pointer border-0">
                    Generate Code
                </button>
            </div>
            <div id="register-result" class="mt-3 text-sm min-h-[1.5rem]"></div>
        </div>

        @if($devices->isEmpty())
            <p class="text-slate-400 text-sm">No devices registered yet.</p>
        @else
        <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr>
                    <th class="text-left py-2 pr-3 text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">Name</th>
                    <th class="text-left py-2 px-3 text-xs text-slate-500 uppercase tracking-wide border-b border-slate-200">Last Seen</th>
                    <th class="border-b border-slate-200"></th>
                </tr>
            </thead>
            <tbody>
            @foreach($devices as $dev)
            <tr class="border-b border-slate-50" id="dev-row-{{ $dev->id }}">
                <td class="py-2.5 pr-3 font-semibold text-slate-800">{{ $dev->name }}</td>
                <td class="py-2.5 px-3 text-slate-600">
                    {{ $dev->last_seen_at ? $dev->last_seen_at->setTimezone('Europe/London')->format('d M Y H:i') : 'Never' }}
                </td>
                <td class="py-2.5 pl-3 text-right">
                    <button onclick="deleteDevice({{ $dev->id }})"
                        class="px-2.5 py-1 border border-red-200 text-red-500 text-xs rounded-md bg-transparent cursor-pointer">
                        Delete
                    </button>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
    @endif

</main>

<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function showRegister() {
    const f = document.getElementById('register-form');
    f.classList.toggle('hidden');
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

async function deleteDevice(id) {
    if (!confirm('Delete this device? The kiosk will need to be re-registered.')) return;
    const r = await fetch(`/clocking/devices/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrfToken },
    });
    if (r.ok) {
        document.getElementById('dev-row-' + id)?.remove();
    }
}
</script>
</x-layout>
