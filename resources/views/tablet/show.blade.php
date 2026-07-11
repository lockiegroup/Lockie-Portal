<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
    <title>{{ $machineName }} — Factory Tablet</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        a[href^="tel"], a[href^="sms"] { color: inherit; text-decoration: none; pointer-events: none; }

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
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            position: sticky;
            top: 0;
            z-index: 10;
            flex-wrap: wrap;
        }
        .tab-header h1 { font-size: 1.1rem; font-weight: 700; color: #f8fafc; letter-spacing: 0.05em; text-transform: uppercase; white-space: nowrap; }
        .tab-header .operator-info {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .tab-header .operator-name {
            font-size: 0.9rem;
            color: #94a3b8;
        }
        .tab-header .operator-name span {
            color: #38bdf8;
            font-weight: 600;
        }
        .op-label { display: inline; }
        @media (max-width: 500px) {
            .tab-header { padding: 10px 14px; }
            .op-label { display: none; }
            .btn-sm { padding: 7px 11px; font-size: 0.78rem; }
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
            font-size: 0.85rem;
            color: #cbd5e1;
            margin-top: 4px;
            font-style: italic;
            font-weight: 600;
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
    {{-- Left: logo + machine name --}}
    <div style="display:flex;align-items:center;gap:10px;white-space:nowrap;flex-shrink:0;">
        <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" style="height:28px;width:auto;opacity:0.9;flex-shrink:0;">
        <h1>{{ $machineName }}</h1>
    </div>

    {{-- Right: operator controls --}}
    <div>
        @if($operator)
            <div class="operator-info">
                <span class="operator-name"><span class="op-label">Operator: </span><span>{{ $operator->name }}</span></span>
                <button class="btn btn-ghost btn-sm" onclick="openSwitchModal()">⇄ Switch Machine</button>
                <form method="POST" action="{{ route('tablet.logout', $machine) }}" style="margin:0;">
                    @csrf
                    <button type="submit" class="btn btn-ghost btn-sm">Sign Out</button>
                </form>
            </div>
        @endif
    </div>
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
        @php
            // Pre-compute per-order card counts and indices for "Card x of x" badge
            $orderGroups    = $jobs->groupBy('order_number');
            $orderCardCounts = $orderGroups->map->count();
            $jobCardIndex   = [];
            foreach ($orderGroups as $orderNum => $groupJobs) {
                foreach ($groupJobs->values() as $idx => $gj) {
                    $jobCardIndex[$gj->id] = $idx + 1;
                }
            }
        @endphp
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
                            <div class="job-comment" style="white-space:pre-wrap;">{{ $job->line_comment }}</div>
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
                            <span style="color:#4ade80;font-weight:600;">{{ number_format($activeRun->progress_packs) }} packs done</span>
                            <span style="color:#475569;"> (updated {{ $activeRun->progress_at->format('H:i') }})</span>
                        @elseif($prevPacks > 0)
                            &nbsp;·&nbsp;
                            <span style="color:#4ade80;font-weight:600;">{{ number_format($prevPacks) }} packs from previous runs</span>
                        @endif
                    </div>
                    <div id="countdown-{{ $job->id }}" style="font-size:0.78rem;color:#64748b;margin-top:5px;"></div>
                @elseif($state === 'paused' && $lastRun)
                    <div class="job-run-info">
                        Paused at {{ $lastRun->ended_at->format('H:i') }}
                        @php $pausedDisplayPacks = $lastRun->packs_produced ?? $lastRun->progress_packs; @endphp
                        @if($pausedDisplayPacks !== null)
                            &nbsp;·&nbsp; <span>{{ number_format($pausedDisplayPacks) }} packs done</span>
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
                        @php $currentPacks = $activeRun->progress_packs !== null ? $activeRun->progress_packs : $prevPacks; @endphp
                        {{-- Progress update --}}
                        <form method="POST" action="{{ route('tablet.jobs.progress', [$machine, $job]) }}"
                            style="display:flex;align-items:center;gap:10px;flex:1;min-width:220px;background:#0f172a;border:2px solid #334155;border-radius:10px;padding:8px 12px;">
                            @csrf
                            <label style="font-size:0.85rem;color:#64748b;white-space:nowrap;font-weight:600;">Packs done:</label>
                            <input type="number" name="progress_packs" min="0" max="{{ $job->order_quantity }}"
                                value="{{ $activeRun->progress_packs !== null ? $activeRun->progress_packs : ($prevPacks ?: '') }}"
                                placeholder="{{ $prevPacks > 0 ? $prevPacks : '0' }}"
                                inputmode="numeric"
                                style="flex:1;background:transparent;border:none;color:#f1f5f9;font-size:1.25rem;font-weight:700;outline:none;width:80px;">
                            <button type="submit" class="btn btn-success btn-sm">Update</button>
                        </form>

                        <button class="btn btn-warning btn-lg"
                            onclick="openPauseModal('{{ route('tablet.jobs.pause', [$machine, $job]) }}', {{ (int)$currentPacks }}, {{ (int)$job->order_quantity }})">
                            ⏸ Pause
                        </button>
                        <button class="btn btn-danger btn-lg"
                            onclick="openEndModal('{{ route('tablet.jobs.end', [$machine, $job]) }}', {{ (int)$currentPacks }}, {{ (int)$job->order_quantity }}, {{ $activeRun->progress_at ? $activeRun->progress_at->timestamp * 1000 : 'null' }})">
                            ■ End Job
                        </button>
                        <button class="btn btn-primary btn-lg"
                            onclick="openHandoverModal('{{ route('tablet.jobs.handover', [$machine, $job]) }}')">
                            ⇄ Handover
                        </button>

                    @elseif($state === 'paused')
                        @php $pausedCurrentPacks = $lastRun->packs_produced ?? $lastRun->progress_packs ?? $prevPacks; @endphp
                        <form method="POST" action="{{ route('tablet.jobs.resume', [$machine, $job]) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn btn-success btn-lg">▶ Resume</button>
                        </form>
                        <button class="btn btn-danger btn-lg"
                            onclick="openEndModal('{{ route('tablet.jobs.end', [$machine, $job]) }}', {{ (int)($pausedCurrentPacks ?? 0) }}, {{ (int)$job->order_quantity }})">
                            ■ End Job
                        </button>

                    @elseif($state === 'done')
                        <form method="POST" action="{{ route('tablet.jobs.start', [$machine, $job]) }}" style="margin:0;">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-md">↺ Start Again</button>
                        </form>
                    @endif

                    @php
                        $cardTotal = $orderCardCounts[$job->order_number] ?? 1;
                        $cardIndex = $jobCardIndex[$job->id] ?? 1;
                    @endphp
                    @if($cardTotal > 1)
                        <span style="margin-left:auto;align-self:center;font-size:0.7rem;font-weight:700;background:#1e3a5f;color:#7dd3fc;padding:2px 8px;border-radius:9999px;text-transform:uppercase;letter-spacing:0.05em;">Card {{ $cardIndex }} of {{ $cardTotal }}</span>
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
                    onclick="_remindLater()">Remind me later (+2h)</button>
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
                <label>Packs produced so far</label>
                <input type="number" id="pause-packs-input" name="packs_produced" min="0" placeholder="0" inputmode="numeric">
            </div>
            <div class="modal-field">
                <label>Reason for pausing</label>
                <div class="reason-buttons">
                    <div class="reason-btn" onclick="selectReason(this, 'Dinner')">Dinner</div>
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
        <form id="end-form" method="POST" onsubmit="endModalSubmit(event)">
            @csrf
            <input type="hidden" name="fully_complete" id="fully-complete-input" value="0">
            <div class="modal-field">
                <label>Total packs done so far</label>
                <div style="display:flex;gap:8px;align-items:center;">
                    <input type="number" id="end-packs-input" name="packs_produced" min="0" placeholder="0" inputmode="numeric" oninput="updateEndRemaining()" style="flex:1;">
                    <button type="button" class="btn btn-ghost btn-sm" onclick="endPacksAll()" style="white-space:nowrap;flex-shrink:0;">All done</button>
                </div>
                <div id="end-remaining-info" style="margin-top:8px;font-size:0.85rem;display:none;"></div>
            </div>
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
    if (loginPin.length < 6) return;
    document.getElementById('pin-input').value = loginPin;
    document.getElementById('pin-form').submit();
}

// ── Pause modal ──
let pauseSelectedReason = '';

function openPauseModal(action, currentPacks, maxPacks) {
    document.getElementById('pause-form').action = action;
    pauseSelectedReason = '';
    // Reset reason buttons (only pause reason buttons, not end modal buttons)
    document.querySelectorAll('#pause-modal .reason-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('pause-reason-input').value = '';
    // Pre-fill packs with last known value
    const packsInput = document.getElementById('pause-packs-input');
    packsInput.max = maxPacks || '';
    packsInput.value = (currentPacks > 0) ? currentPacks : '';
    document.getElementById('pause-modal').classList.add('open');
}

function selectReason(btn, reason) {
    pauseSelectedReason = reason;
    document.querySelectorAll('#pause-modal .reason-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('pause-reason-input').value = reason !== 'Other' ? reason : '';
    if (reason === 'Other') document.getElementById('pause-reason-input').focus();
}

// ── End modal ──
function openEndModal(action, currentPacks, maxPacks, progressAtMs) {
    document.getElementById('end-form').action = action;
    document.getElementById('end-form').dataset.progressAt = progressAtMs ?? '';
    document.getElementById('end-form').dataset.maxPacks = maxPacks || '0';
    const packsInput = document.getElementById('end-packs-input');
    packsInput.max = maxPacks || '';
    packsInput.value = (currentPacks > 0) ? currentPacks : '';
    updateEndRemaining();
    document.getElementById('end-modal').classList.add('open');
}

function endPacksAll() {
    const maxPacks = parseInt(document.getElementById('end-form').dataset.maxPacks || '0');
    if (maxPacks) {
        document.getElementById('end-packs-input').value = maxPacks;
        updateEndRemaining();
    }
}

function updateEndRemaining() {
    const info     = document.getElementById('end-remaining-info');
    const input    = document.getElementById('end-packs-input');
    const maxPacks = parseInt(document.getElementById('end-form').dataset.maxPacks || '0');
    if (!maxPacks) { info.style.display = 'none'; return; }
    const done      = parseInt(input.value) || 0;
    const remaining = maxPacks - done;
    const fullyDone = done >= maxPacks;
    document.getElementById('fully-complete-input').value = fullyDone ? '1' : '0';
    if (remaining > 0) {
        info.style.display = 'block';
        info.style.color   = '#fbbf24';
        info.textContent   = `⚠ ${remaining.toLocaleString()} pack${remaining === 1 ? '' : 's'} still remaining — job will be marked as part complete`;
    } else if (remaining === 0) {
        info.style.display = 'block';
        info.style.color   = '#4ade80';
        info.textContent   = `✓ All ${maxPacks.toLocaleString()} packs complete — job will be marked as fully done`;
    } else {
        info.style.display = 'block';
        info.style.color   = '#f87171';
        info.textContent   = `Over by ${Math.abs(remaining).toLocaleString()} packs (order was ${maxPacks.toLocaleString()})`;
    }
}

function endModalSubmit(e) {
    const form        = document.getElementById('end-form');
    const progressAt  = form.dataset.progressAt ? parseInt(form.dataset.progressAt) : null;
    const fullyDone   = document.getElementById('fully-complete-input').value === '1';

    if (!fullyDone && progressAt) {
        const diffMs   = Date.now() - progressAt;
        const diffMins = Math.round(diffMs / 60000);
        if (diffMins >= 10) {
            const hrs  = Math.floor(diffMins / 60);
            const mins = diffMins % 60;
            const ago  = hrs > 0 ? `${hrs}h ${mins}m` : `${mins} minutes`;
            const ok = confirm(
                `You last logged your packs ${ago} ago.\n\n` +
                `Ending now means you are confirming no additional packs have been done since then.\n\n` +
                `Are you sure?`
            );
            if (!ok) { e.preventDefault(); return; }
        }
    }
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

// ── 2-hour progress reminder (localStorage-backed so page refresh preserves snooze) ──
@php
    $reminderJobs = $jobs->filter(function($j) {
        return $j->runs->first(fn($r) => $r->ended_at === null) !== null;
    })->map(function($j) use ($machine) {
        $activeRun = $j->runs->first(fn($r) => $r->ended_at === null);
        return [
            'id'          => $j->id,
            'name'        => $j->customer_name,
            'progressUrl' => route('tablet.jobs.progress', [$machine, $j->id]),
            'lastUpdate'  => ($activeRun->progress_at ?? $activeRun->started_at)->timestamp * 1000,
        ];
    })->values();
@endphp
const _reminderJobs   = @json($reminderJobs);
const _TWO_HOURS      = 2 * 60 * 60 * 1000;
const _SNOOZE_KEY     = 'reminderSnoozeUntil_{{ $machine }}';

function _isSnoozed() {
    const until = parseInt(localStorage.getItem(_SNOOZE_KEY) || '0', 10);
    return Date.now() < until;
}

function _snooze2h() {
    localStorage.setItem(_SNOOZE_KEY, Date.now() + _TWO_HOURS);
}

function _remindLater() {
    _snooze2h();
    closeModal('reminder-modal');
}

function _checkReminder() {
    if (_isSnoozed()) return;
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
        document.getElementById('reminder-job-name').textContent = mostOverdue.name;
        document.getElementById('reminder-form').action = mostOverdue.progressUrl;
        // Pre-fill reminder packs input with the inline progress input value if available
        const reminderInput = document.querySelector('#reminder-form input[name="progress_packs"]');
        if (reminderInput) reminderInput.value = '';
        document.getElementById('reminder-modal').classList.add('open');
    }
}

// ── Countdown timers on each running job card ──
function _fmtCountdown(ms) {
    if (ms <= 0) return null;
    const totalMins = Math.ceil(ms / 60000);
    const h = Math.floor(totalMins / 60);
    const m = totalMins % 60;
    return h > 0 ? h + 'h ' + m + 'm' : m + 'm';
}

function _updateCountdowns() {
    const now = Date.now();
    _reminderJobs.forEach(function(job) {
        const el = document.getElementById('countdown-' + job.id);
        if (!el) return;
        const remaining = _TWO_HOURS - (now - job.lastUpdate);
        if (remaining <= 0) {
            const overdueMins = Math.floor((now - job.lastUpdate - _TWO_HOURS) / 60000);
            const lastTime = new Date(job.lastUpdate).toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'});
            let overdueStr;
            if (overdueMins < 60) {
                overdueStr = overdueMins + ' min' + (overdueMins !== 1 ? 's' : '');
            } else {
                const h = Math.floor(overdueMins / 60);
                const m = overdueMins % 60;
                overdueStr = h + 'h' + (m > 0 ? ' ' + m + 'm' : '');
            }
            el.textContent = '⚠ Qty update overdue — last logged ' + lastTime + ', ' + overdueStr + ' overdue';
            el.style.color = '#ef4444';
            el.style.fontWeight = '600';
        } else {
            const fmt = _fmtCountdown(remaining);
            el.textContent = 'Qty update due in ' + fmt;
            el.style.fontWeight = '400';
            el.style.color = remaining < 20 * 60 * 1000 ? '#f97316' : '#64748b';
        }
    });
}

// Schedule each running job's first reminder trigger
_reminderJobs.forEach(function(job) {
    const delay = Math.max(0, _TWO_HOURS - (Date.now() - job.lastUpdate));
    setTimeout(_checkReminder, delay);
});

setInterval(_checkReminder, 60 * 1000);
setInterval(_updateCountdowns, 30 * 1000);
_checkReminder();
_updateCountdowns();
@endif
</script>

</body>
</html>
