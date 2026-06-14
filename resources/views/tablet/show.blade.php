<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $machineName }} — Factory Tablet</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #0f172a;
            color: #f1f5f9;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            min-height: 100vh;
            -webkit-tap-highlight-color: transparent;
        }

        /* ── Header ── */
        .tab-header {
            background: #1e293b;
            border-bottom: 2px solid #334155;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .tab-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        .tab-header .operator-info {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .tab-header .operator-name {
            font-size: 1rem;
            color: #94a3b8;
        }
        .tab-header .operator-name span {
            color: #38bdf8;
            font-weight: 600;
        }

        /* ── Buttons ── */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.15s, transform 0.1s;
            white-space: nowrap;
            text-decoration: none;
        }
        .btn:active { transform: scale(0.97); }
        .btn-lg { padding: 14px 28px; font-size: 1rem; min-height: 52px; }
        .btn-md { padding: 10px 20px; font-size: 0.9rem; min-height: 44px; }
        .btn-sm { padding: 8px 14px; font-size: 0.8rem; min-height: 36px; }

        .btn-primary  { background: #0284c7; color: #fff; }
        .btn-primary:hover  { background: #0369a1; }
        .btn-success  { background: #16a34a; color: #fff; }
        .btn-success:hover  { background: #15803d; }
        .btn-warning  { background: #d97706; color: #fff; }
        .btn-warning:hover  { background: #b45309; }
        .btn-danger   { background: #dc2626; color: #fff; }
        .btn-danger:hover   { background: #b91c1c; }
        .btn-ghost    { background: #334155; color: #cbd5e1; }
        .btn-ghost:hover    { background: #475569; }
        .btn-outline  { background: transparent; border: 2px solid #475569; color: #94a3b8; }
        .btn-outline:hover  { border-color: #64748b; color: #cbd5e1; }

        /* ── Login Screen ── */
        .login-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: calc(100vh - 80px);
            padding: 24px;
            gap: 32px;
        }
        .login-title {
            text-align: center;
        }
        .login-title h2 {
            font-size: 1.75rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 6px;
        }
        .login-title p {
            color: #64748b;
            font-size: 1rem;
        }

        .pin-display {
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 14px;
            padding: 20px 40px;
            font-size: 2.5rem;
            letter-spacing: 12px;
            min-width: 280px;
            text-align: center;
            color: #38bdf8;
            font-family: monospace;
        }

        .pin-pad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            width: 300px;
        }
        .pin-key {
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 12px;
            height: 72px;
            font-size: 1.5rem;
            font-weight: 700;
            color: #f1f5f9;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.1s, transform 0.1s;
            user-select: none;
        }
        .pin-key:active { background: #334155; transform: scale(0.95); }
        .pin-key.key-back { color: #f87171; font-size: 1.25rem; }
        .pin-key.key-submit { background: #0284c7; color: #fff; border-color: #0284c7; }
        .pin-key.key-submit:active { background: #0369a1; }

        .pin-error {
            background: #450a0a;
            border: 1px solid #dc2626;
            color: #fca5a5;
            padding: 12px 20px;
            border-radius: 10px;
            font-size: 0.9rem;
            text-align: center;
            max-width: 300px;
        }

        /* ── Job List ── */
        .jobs-screen {
            padding: 20px 24px;
            max-width: 900px;
            margin: 0 auto;
        }
        .jobs-screen h2 {
            font-size: 1.1rem;
            color: #64748b;
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .no-jobs {
            background: #1e293b;
            border: 2px dashed #334155;
            border-radius: 16px;
            padding: 48px 24px;
            text-align: center;
            color: #475569;
            font-size: 1.1rem;
        }

        .job-card {
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 16px;
            padding: 20px 24px;
            margin-bottom: 16px;
            transition: border-color 0.2s;
        }
        .job-card.state-running { border-color: #16a34a; }
        .job-card.state-paused  { border-color: #d97706; }

        .job-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 12px;
        }
        .job-card-info h3 {
            font-size: 1.1rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 4px;
        }
        .job-card-info .job-meta {
            font-size: 0.85rem;
            color: #64748b;
        }
        .job-card-info .job-desc {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-top: 2px;
        }
        .job-card-info .job-comment {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 4px;
            font-style: italic;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .badge-idle    { background: #1e3a5f; color: #60a5fa; }
        .badge-running { background: #14532d; color: #4ade80; }
        .badge-paused  { background: #451a03; color: #fbbf24; }
        .badge-done    { background: #1e3a5f; color: #94a3b8; }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
        }
        .badge-running .status-dot {
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.5; transform: scale(0.7); }
        }

        .job-run-info {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 6px;
        }
        .job-run-info span { color: #94a3b8; }

        .job-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #334155;
        }

        /* ── Modals ── */
        .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.75);
            z-index: 100;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .modal-backdrop.open { display: flex; }

        .modal {
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 20px;
            padding: 28px;
            width: 100%;
            max-width: 420px;
        }
        .modal h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: #f8fafc;
            margin-bottom: 20px;
        }
        .modal-field {
            margin-bottom: 16px;
        }
        .modal-field label {
            display: block;
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .modal-field input[type="number"],
        .modal-field input[type="text"] {
            width: 100%;
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 10px;
            padding: 12px 16px;
            color: #f1f5f9;
            font-size: 1.1rem;
            outline: none;
        }
        .modal-field input:focus { border-color: #0284c7; }

        .reason-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 8px;
        }
        .reason-btn {
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 8px;
            padding: 10px 8px;
            color: #94a3b8;
            font-size: 0.85rem;
            cursor: pointer;
            text-align: center;
            transition: all 0.15s;
        }
        .reason-btn.active, .reason-btn:hover { border-color: #0284c7; color: #38bdf8; background: #0f2a45; }

        .modal-pin-display {
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 10px;
            padding: 14px;
            font-size: 1.5rem;
            letter-spacing: 8px;
            text-align: center;
            color: #38bdf8;
            font-family: monospace;
            margin-bottom: 12px;
            min-height: 56px;
        }

        .modal-pin-pad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
            margin-bottom: 16px;
        }
        .mpk {
            background: #0f172a;
            border: 2px solid #334155;
            border-radius: 8px;
            height: 52px;
            font-size: 1.2rem;
            font-weight: 700;
            color: #f1f5f9;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            user-select: none;
            transition: background 0.1s;
        }
        .mpk:active { background: #334155; }
        .mpk.mpk-back { color: #f87171; }

        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-actions .btn { flex: 1; }

        .modal-error {
            background: #450a0a;
            border: 1px solid #dc2626;
            color: #fca5a5;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 12px;
        }
    </style>
</head>
<body>

{{-- ════════════════════════════════════════════
     HEADER
     ════════════════════════════════════════════ --}}
<div class="tab-header">
    <h1>{{ $machineName }}</h1>

    @if($operator)
        <div class="operator-info">
            <span class="operator-name">Operator: <span>{{ $operator->name }}</span></span>
            <button class="btn btn-ghost btn-sm" onclick="openSwitchModal()">⇄ Switch Machine</button>
            <form method="POST" action="{{ route('tablet.logout', $machine) }}" style="margin:0;">
                @csrf
                <button type="submit" class="btn btn-ghost btn-sm">Sign Out</button>
            </form>
        </div>
    @endif
</div>

{{-- ════════════════════════════════════════════
     PIN LOGIN SCREEN
     ════════════════════════════════════════════ --}}
@if(!$operator)
<div class="login-screen">

    <div class="login-title">
        <h2>Operator Sign In</h2>
        <p>Enter your PIN to continue</p>
    </div>

    @if(session('pin_error'))
        <div class="pin-error">{{ session('pin_error') }}</div>
    @endif

    <div class="pin-display" id="pin-display">—</div>

    <div class="pin-pad">
        @foreach([1,2,3,4,5,6,7,8,9] as $d)
            <div class="pin-key" onclick="pinDigit('{{ $d }}')">{{ $d }}</div>
        @endforeach
        <div class="pin-key key-back" onclick="pinBack()">⌫</div>
        <div class="pin-key" onclick="pinDigit('0')">0</div>
        <div class="pin-key key-submit" onclick="pinSubmit()">✓</div>
    </div>

    <form id="pin-form" method="POST" action="{{ route('tablet.login', $machine) }}" style="display:none;">
        @csrf
        <input type="hidden" name="pin" id="pin-input">
    </form>

</div>

{{-- ════════════════════════════════════════════
     JOB MANAGEMENT SCREEN
     ════════════════════════════════════════════ --}}
@else
<div class="jobs-screen">

    @if(session('error'))
        <div style="background:#450a0a;border:1px solid #dc2626;color:#fca5a5;padding:12px 16px;border-radius:10px;margin-bottom:16px;">
            {{ session('error') }}
        </div>
    @endif

    @if(session('start_blocked'))
        <div style="background:#431407;border:2px solid #ea580c;color:#fed7aa;padding:14px 16px;border-radius:12px;margin-bottom:16px;display:flex;align-items:center;gap:12px;">
            <span style="font-size:1.25rem;flex-shrink:0;">⚠️</span>
            <div>
                <div style="font-weight:700;font-size:1rem;margin-bottom:3px;">Can't start — another job is already running</div>
                <div style="font-size:0.875rem;opacity:0.85;">
                    <strong>{{ session('start_blocked') }}</strong> is currently running on this machine.
                    Please <strong>pause</strong> or <strong>end</strong> that job before starting a new one.
                </div>
            </div>
        </div>
    @endif

    @if(session('handover_error'))
        <div style="background:#450a0a;border:1px solid #dc2626;color:#fca5a5;padding:12px 16px;border-radius:10px;margin-bottom:16px;">
            {{ session('handover_error') }}
        </div>
    @endif

    <h2>Jobs on this machine</h2>

    @if($jobs->isEmpty())
        <div class="no-jobs">
            No jobs currently assigned to {{ $machineName }}.
        </div>
    @else
        @foreach($jobs as $job)
            @php
                $activeRun    = $job->runs->first(fn($r) => $r->ended_at === null);
                $lastRun      = $job->runs->last();
                $endedRuns    = $job->runs->filter(fn($r) => $r->ended_at !== null);
                $prevPacks    = $endedRuns->sum('packs_produced'); // cumulative from all ended runs
                if ($activeRun) {
                    $state = 'running';
                } elseif ($lastRun && $lastRun->end_reason === 'pause') {
                    $state = 'paused';
                } elseif ($lastRun && $lastRun->end_reason === 'complete') {
                    $state = 'done';
                } else {
                    $state = 'idle';
                }
            @endphp

            <div class="job-card state-{{ $state }}">
                <div class="job-card-header">
                    <div class="job-card-info">
                        <h3>{{ $job->customer_name }}</h3>
                        <div class="job-meta">
                            #{{ $job->order_number }}
                            &nbsp;·&nbsp; {{ $job->product_code }}
                            &nbsp;·&nbsp; {{ number_format($job->order_quantity) }} packs
                            @if($job->quantity_completed > 0)
                                &nbsp;·&nbsp; {{ number_format($job->quantity_completed) }} done
                            @endif
                        </div>
                        @if($job->product_description)
                            <div class="job-desc">{{ $job->product_description }}</div>
                        @endif
                        @if($job->line_comment)
                            <div class="job-comment">{{ $job->line_comment }}</div>
                        @endif
                    </div>

                    <div>
                        @if($state === 'running')
                            <div class="status-badge badge-running">
                                <div class="status-dot"></div> Running
                            </div>
                        @elseif($state === 'paused')
                            <div class="status-badge badge-paused">
                                <div class="status-dot"></div> Paused
                            </div>
                        @elseif($state === 'done')
                            <div class="status-badge badge-done">
                                <div class="status-dot"></div> Ended
                            </div>
                        @else
                            <div class="status-badge badge-idle">
                                <div class="status-dot"></div> Not Started
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Run info --}}
                @if($activeRun)
                    <div class="job-run-info">
                        Started {{ $activeRun->started_at->format('H:i') }}
                        by <span>{{ $activeRun->user?->name ?? 'Unknown' }}</span>
                        @if($activeRun->progress_packs !== null)
                            &nbsp;·&nbsp;
                            <span style="color:#4ade80;font-weight:600;">{{ number_format($activeRun->progress_packs + $prevPacks) }} packs total</span>
                            <span style="color:#475569;"> (updated {{ $activeRun->progress_at->format('H:i') }})</span>
                        @elseif($prevPacks > 0)
                            &nbsp;·&nbsp;
                            <span style="color:#4ade80;font-weight:600;">{{ number_format($prevPacks) }} packs before this run</span>
                        @endif
                    </div>
                @elseif($state === 'paused' && $lastRun)
                    <div class="job-run-info">
                        Paused at {{ $lastRun->ended_at->format('H:i') }}
                        @if($lastRun->packs_produced !== null)
                            &nbsp;·&nbsp; <span>{{ number_format($lastRun->packs_produced) }} packs done</span>
                        @endif
                        @if($lastRun->pause_reason)
                            &nbsp;·&nbsp; <span>{{ $lastRun->pause_reason }}</span>
                        @endif
                    </div>
                @endif

                {{-- Actions --}}
                <div class="job-actions">

                    @if($state === 'idle')
                        <form method="POST" action="{{ route('tablet.jobs.start', [$machine, $job]) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg">▶ Start Job</button>
                        </form>

                    @elseif($state === 'running')
                        {{-- Progress update --}}
                        <form method="POST" action="{{ route('tablet.jobs.progress', [$machine, $job]) }}"
                            style="display:flex;align-items:center;gap:10px;flex:1;min-width:220px;background:#0f172a;border:2px solid #334155;border-radius:10px;padding:8px 12px;">
                            @csrf
                            <label style="font-size:0.85rem;color:#64748b;white-space:nowrap;font-weight:600;">Packs done:</label>
                            <input type="number" name="progress_packs" min="0"
                                value="{{ $activeRun->progress_packs !== null ? $activeRun->progress_packs + $prevPacks : ($prevPacks ?: '') }}"
                                placeholder="{{ $prevPacks > 0 ? $prevPacks : '0' }}"
                                inputmode="numeric"
                                style="flex:1;background:transparent;border:none;color:#f1f5f9;font-size:1.25rem;font-weight:700;outline:none;width:80px;">
                            <button type="submit" class="btn btn-success btn-sm">Update</button>
                        </form>

                        <button class="btn btn-warning btn-lg"
                            onclick="openPauseModal('{{ route('tablet.jobs.pause', [$machine, $job]) }}')">
                            ⏸ Pause
                        </button>
                        <button class="btn btn-danger btn-lg"
                            onclick="openEndModal('{{ route('tablet.jobs.end', [$machine, $job]) }}')">
                            ■ End Job
                        </button>
                        <button class="btn btn-primary btn-lg"
                            onclick="openHandoverModal('{{ route('tablet.jobs.handover', [$machine, $job]) }}')">
                            ⇄ Handover
                        </button>

                    @elseif($state === 'paused')
                        <form method="POST" action="{{ route('tablet.jobs.resume', [$machine, $job]) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg">▶ Resume</button>
                        </form>
                        <button class="btn btn-danger btn-lg"
                            onclick="openEndModal('{{ route('tablet.jobs.end', [$machine, $job]) }}')">
                            ■ End Job
                        </button>

                    @elseif($state === 'done')
                        <form method="POST" action="{{ route('tablet.jobs.start', [$machine, $job]) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-md">↺ Start Again</button>
                        </form>
                    @endif

                </div>
            </div>
        @endforeach
    @endif

</div>
@endif


{{-- ════════════════════════════════════════════
     SWITCH MACHINE MODAL
     ════════════════════════════════════════════ --}}
@if($operator)
<div class="modal-backdrop" id="switch-modal">
    <div class="modal" style="max-width:480px;">
        <h3>⇄ Switch Machine</h3>

        <div id="switch-warning" style="display:none;background:#431407;border:1px solid #ea580c;border-radius:10px;padding:12px 14px;margin-bottom:20px;color:#fed7aa;font-size:0.9rem;">
            <strong>⚠️ A job is currently running.</strong><br>
            Please <strong>pause or end</strong> the current job before switching machines.
        </div>

        <p style="color:#64748b;font-size:0.875rem;margin-bottom:20px;">Select the machine you want to move to:</p>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
            @foreach(\App\Models\PrintJob::MACHINES as $m)
                @if($m !== $machine)
                    <a href="{{ route('tablet.show', $m) }}"
                        class="machine-switch-btn btn btn-ghost btn-lg"
                        style="text-decoration:none;display:flex;align-items:center;justify-content:center;font-size:1rem;">
                        {{ ucwords(str_replace('_', ' ', $m)) }}
                    </a>
                @else
                    <div style="display:flex;align-items:center;justify-content:center;height:52px;border-radius:10px;border:2px solid #334155;color:#475569;font-size:1rem;font-weight:600;background:#0f172a;opacity:0.5;">
                        {{ ucwords(str_replace('_', ' ', $m)) }} ← here
                    </div>
                @endif
            @endforeach
        </div>

        <div class="modal-actions" style="margin-top:20px;">
            <button type="button" class="btn btn-ghost btn-md" onclick="closeModal('switch-modal')" style="flex:none;">Cancel</button>
        </div>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════
     UPDATE BANNER (real-time poll detected change)
     ════════════════════════════════════════════ --}}
@if($operator)
<div id="update-banner" style="display:none;position:fixed;top:0;left:0;right:0;z-index:200;background:#0284c7;color:#fff;padding:14px 24px;align-items:center;justify-content:center;gap:10px;font-weight:600;font-size:1rem;">
    <svg style="width:18px;height:18px;animation:spin 1s linear infinite;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M21 12a9 9 0 1 1-9-9"/><path d="M21 3v4h-4"/>
    </svg>
    Job schedule updated — refreshing…
</div>
<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
@endif

{{-- ════════════════════════════════════════════
     2-HOUR REMINDER MODAL
     ════════════════════════════════════════════ --}}
@if($operator)
<div class="modal-backdrop" id="reminder-modal">
    <div class="modal" style="border-color:#d97706;">
        <h3 style="color:#fbbf24;">⏱ Progress Update Due</h3>
        <p id="reminder-job-name" style="color:#94a3b8;margin-bottom:8px;font-size:0.9rem;"></p>
        <p style="color:#64748b;font-size:0.85rem;margin-bottom:20px;">
            It's been 2 hours since the last update. Please log the current packs count.
        </p>
        <form id="reminder-form" method="POST">
            @csrf
            <div class="modal-field">
                <label>Packs produced so far</label>
                <input type="number" name="progress_packs" min="0" placeholder="0" inputmode="numeric" required
                    style="font-size:2rem;font-weight:700;text-align:center;padding:16px;">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost btn-md"
                    onclick="closeModal('reminder-modal')">Remind me later</button>
                <button type="submit" class="btn btn-success btn-md">Log Update</button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ════════════════════════════════════════════
     PAUSE MODAL
     ════════════════════════════════════════════ --}}
<div class="modal-backdrop" id="pause-modal">
    <div class="modal">
        <h3>⏸ Pause Job</h3>
        <form id="pause-form" method="POST">
            @csrf
            <div class="modal-field">
                <label>Packs produced so far (optional)</label>
                <input type="number" name="packs_produced" min="0" placeholder="0" inputmode="numeric">
            </div>
            <div class="modal-field">
                <label>Reason for pausing</label>
                <div class="reason-buttons">
                    <div class="reason-btn" onclick="selectReason(this, 'Material issue')">Material issue</div>
                    <div class="reason-btn" onclick="selectReason(this, 'Machine breakdown')">Machine breakdown</div>
                    <div class="reason-btn" onclick="selectReason(this, 'End of shift')">End of shift</div>
                    <div class="reason-btn" onclick="selectReason(this, 'Other')">Other</div>
                </div>
                <input type="text" name="pause_reason" id="pause-reason-input" placeholder="Or type a reason…"
                    style="width:100%;background:#0f172a;border:2px solid #334155;border-radius:10px;padding:10px 14px;color:#f1f5f9;font-size:0.9rem;outline:none;margin-top:4px;">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost btn-md" onclick="closeModal('pause-modal')">Cancel</button>
                <button type="submit" class="btn btn-warning btn-md">Pause Job</button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════
     END JOB MODAL
     ════════════════════════════════════════════ --}}
<div class="modal-backdrop" id="end-modal">
    <div class="modal">
        <h3>■ End Job</h3>
        <form id="end-form" method="POST">
            @csrf
            <div class="modal-field">
                <label>Packs produced this run (optional)</label>
                <input type="number" name="packs_produced" min="0" placeholder="0" inputmode="numeric">
            </div>
            <p style="color:#64748b;font-size:0.85rem;margin-bottom:4px;">
                This will mark the run as complete. The job will remain on the board.
            </p>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost btn-md" onclick="closeModal('end-modal')">Cancel</button>
                <button type="submit" class="btn btn-danger btn-md">End Job</button>
            </div>
        </form>
    </div>
</div>

{{-- ════════════════════════════════════════════
     HANDOVER MODAL
     ════════════════════════════════════════════ --}}
<div class="modal-backdrop" id="handover-modal">
    <div class="modal">
        <h3>⇄ Handover to New Operator</h3>
        <form id="handover-form" method="POST">
            @csrf
            <input type="hidden" name="new_pin" id="handover-pin-input">
            <div class="modal-field">
                <label>Packs produced so far (optional)</label>
                <input type="number" name="packs_produced" min="0" placeholder="0" inputmode="numeric">
            </div>
            <div class="modal-field">
                <label>New operator PIN</label>
                <div class="modal-pin-display" id="handover-pin-display">—</div>
                <div class="modal-pin-pad">
                    @foreach([1,2,3,4,5,6,7,8,9] as $d)
                        <div class="mpk" onclick="hPinDigit('{{ $d }}')">{{ $d }}</div>
                    @endforeach
                    <div class="mpk mpk-back" onclick="hPinBack()">⌫</div>
                    <div class="mpk" onclick="hPinDigit('0')">0</div>
                    <div class="mpk" style="background:#0284c7;color:#fff;" onclick="hPinDone()">✓</div>
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost btn-md" onclick="closeModal('handover-modal')">Cancel</button>
                <button type="submit" class="btn btn-primary btn-md">Complete Handover</button>
            </div>
        </form>
    </div>
</div>

<script>
// ── Login PIN pad ──
let loginPin = '';

function pinDigit(d) {
    if (loginPin.length < 8) {
        loginPin += d;
        updateLoginDisplay();
    }
}
function pinBack() {
    loginPin = loginPin.slice(0, -1);
    updateLoginDisplay();
}
function updateLoginDisplay() {
    const el = document.getElementById('pin-display');
    el.textContent = loginPin.length ? '●'.repeat(loginPin.length) : '—';
}
function pinSubmit() {
    if (loginPin.length < 4) return;
    document.getElementById('pin-input').value = loginPin;
    document.getElementById('pin-form').submit();
}

// ── Pause modal ──
let pauseSelectedReason = '';

function openPauseModal(action) {
    document.getElementById('pause-form').action = action;
    pauseSelectedReason = '';
    document.querySelectorAll('.reason-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('pause-reason-input').value = '';
    document.getElementById('pause-modal').classList.add('open');
}

function selectReason(btn, reason) {
    pauseSelectedReason = reason;
    document.querySelectorAll('.reason-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('pause-reason-input').value = reason !== 'Other' ? reason : '';
    if (reason === 'Other') document.getElementById('pause-reason-input').focus();
}

// ── End modal ──
function openEndModal(action) {
    document.getElementById('end-form').action = action;
    document.getElementById('end-modal').classList.add('open');
}

// ── Handover modal ──
let handoverPin = '';

function openHandoverModal(action) {
    handoverPin = '';
    document.getElementById('handover-pin-input').value = '';
    updateHandoverDisplay();
    document.getElementById('handover-form').action = action;
    document.getElementById('handover-modal').classList.add('open');
}

function hPinDigit(d) {
    if (handoverPin.length < 8) {
        handoverPin += d;
        updateHandoverDisplay();
    }
}
function hPinBack() {
    handoverPin = handoverPin.slice(0, -1);
    updateHandoverDisplay();
}
function updateHandoverDisplay() {
    const el = document.getElementById('handover-pin-display');
    el.textContent = handoverPin.length ? '●'.repeat(handoverPin.length) : '—';
}
function hPinDone() {
    document.getElementById('handover-pin-input').value = handoverPin;
}

// ── Switch machine modal ──
function openSwitchModal() {
    const hasRunning = document.querySelectorAll('.state-running').length > 0;
    const warning    = document.getElementById('switch-warning');
    const btns       = document.querySelectorAll('.machine-switch-btn');
    if (warning) {
        warning.style.display = hasRunning ? 'block' : 'none';
    }
    btns.forEach(function(btn) {
        btn.style.opacity        = hasRunning ? '0.35' : '1';
        btn.style.pointerEvents  = hasRunning ? 'none' : '';
        btn.style.cursor         = hasRunning ? 'not-allowed' : '';
    });
    document.getElementById('switch-modal').classList.add('open');
}

// ── Close any modal ──
function closeModal(id) {
    document.getElementById(id).classList.remove('open');
    // If reminder was dismissed, allow it to re-appear after 30 minutes
    if (id === 'reminder-modal') {
        setTimeout(function() { _reminderShown = false; _checkReminder(); }, 30 * 60 * 1000);
    }
}

// Close on backdrop click
document.querySelectorAll('.modal-backdrop').forEach(function(el) {
    el.addEventListener('click', function(e) {
        if (e.target === el) el.classList.remove('open');
    });
});

@if($operator)
// ── Job list polling (real-time updates) ──
@php
    $jobsHash = md5($jobs->map(fn($j) => $j->id . ':' . $j->position)->implode(','));
@endphp
const _currentHash = '{{ $jobsHash }}';
const _hashUrl     = '{{ route('tablet.jobs.hash', $machine) }}';
let _reloading     = false;

function _pollJobs() {
    if (_reloading) return;
    fetch(_hashUrl, { cache: 'no-store' })
        .then(r => r.json())
        .then(data => {
            if (data.hash !== _currentHash && !_reloading) {
                _reloading = true;
                const banner = document.getElementById('update-banner');
                if (banner) {
                    banner.style.display = 'flex';
                    setTimeout(() => location.reload(), 2000);
                } else {
                    location.reload();
                }
            }
        })
        .catch(() => {});
}
setInterval(_pollJobs, 30000);

// ── 2-hour progress reminder ──
@php
    $reminderJobs = $jobs->filter(fn($j) => $j->runs->first() !== null)->map(fn($j) => [
        'id'          => $j->id,
        'name'        => $j->customer_name,
        'progressUrl' => route('tablet.jobs.progress', [$machine, $j->id]),
        'lastUpdate'  => ($j->runs->first()->progress_at ?? $j->runs->first()->started_at)->timestamp * 1000,
    ])->values();
@endphp
const _reminderJobs = @json($reminderJobs);
const _TWO_HOURS    = 2 * 60 * 60 * 1000;
let _reminderShown  = false;

function _checkReminder() {
    if (_reminderShown) return;
    const now = Date.now();
    let mostOverdue = null, maxElapsed = 0;
    _reminderJobs.forEach(function(job) {
        const elapsed = now - job.lastUpdate;
        if (elapsed >= _TWO_HOURS && elapsed > maxElapsed) {
            maxElapsed = elapsed;
            mostOverdue = job;
        }
    });
    if (mostOverdue) {
        _reminderShown = true;
        document.getElementById('reminder-job-name').textContent = mostOverdue.name;
        document.getElementById('reminder-form').action = mostOverdue.progressUrl;
        document.getElementById('reminder-modal').classList.add('open');
    }
}

// Schedule each running job's reminder
_reminderJobs.forEach(function(job) {
    const elapsed = Date.now() - job.lastUpdate;
    const delay   = Math.max(0, _TWO_HOURS - elapsed);
    setTimeout(_checkReminder, delay);
});
// Also recheck every minute in case it was dismissed and more time passes
setInterval(_checkReminder, 60 * 1000);
_checkReminder();
@endif
</script>

</body>
</html>
