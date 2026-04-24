<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? 'Lockie Portal' }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css'])
    @auth
    <style>
        /* ── Sidebar shell ─────────────────────────────────────── */
        #sidebar {
            position: fixed; top: 0; left: 0; height: 100vh; width: 260px;
            background: #0f172a; display: flex; flex-direction: column;
            z-index: 50; transition: width 0.2s ease; overflow: hidden;
        }
        #page-content  { margin-left: 260px; min-height: 100vh; transition: margin-left 0.2s ease; }
        #sb-overlay    { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 49; }
        #mobile-topbar { display: none; }

        /* ── Collapsed state ───────────────────────────────────── */
        body.sb-collapsed #sidebar       { width: 68px; }
        body.sb-collapsed #page-content  { margin-left: 68px; }
        body.sb-collapsed #sb-user-info  { display: none; }
        body.sb-collapsed .sb-label,
        body.sb-collapsed .sb-section,
        body.sb-collapsed .sb-sub-group  { display: none !important; }
        body.sb-collapsed .sb-item       { justify-content: center; padding-left: 0; padding-right: 0; }

        /* Collapsed: hover tooltips */
        body.sb-collapsed .sb-item       { position: relative; }
        body.sb-collapsed .sb-item::after {
            content: attr(data-tip);
            position: absolute; left: calc(100% + 10px); top: 50%; transform: translateY(-50%);
            background: #1e293b; color: #e2e8f0; font-size: 0.75rem; font-weight: 500;
            padding: 5px 10px; border-radius: 6px; white-space: nowrap;
            opacity: 0; pointer-events: none; transition: opacity 0.12s;
            border: 1px solid #334155; z-index: 100;
        }
        body.sb-collapsed .sb-item:hover::after { opacity: 1; }

        /* ── Nav item styles ───────────────────────────────────── */
        .sb-item {
            display: flex; align-items: center; gap: 10px;
            padding: 9px 12px; border-radius: 8px; margin-bottom: 2px;
            color: #64748b; font-size: 0.8125rem; font-weight: 500;
            text-decoration: none; white-space: nowrap;
            transition: background 0.12s, color 0.12s; cursor: pointer;
        }
        .sb-item:hover     { background: rgba(255,255,255,0.06); color: #cbd5e1; }
        .sb-item.sb-active { background: rgba(225,29,72,0.12); color: #fb7185; }

        .sb-sub-item {
            display: flex; align-items: center;
            padding: 6px 12px 6px 42px; border-radius: 6px; margin-bottom: 1px;
            color: #475569; font-size: 0.8rem; font-weight: 500;
            text-decoration: none; white-space: nowrap; transition: background 0.12s, color 0.12s;
        }
        .sb-sub-item:hover     { background: rgba(255,255,255,0.04); color: #94a3b8; }
        .sb-sub-item.sb-active { color: #fb7185; }

        .sb-icon { width: 17px; height: 17px; flex-shrink: 0; }
        .sb-nav::-webkit-scrollbar { display: none; }

        /* ── Mobile ────────────────────────────────────────────── */
        @media (max-width: 767px) {
            #sidebar       { transform: translateX(-260px); width: 260px !important; transition: transform 0.22s ease; }
            #page-content  { margin-left: 0 !important; }
            #mobile-topbar { display: flex; }
            body.sb-open #sidebar    { transform: translateX(0); }
            body.sb-open #sb-overlay { display: block; }
        }
    </style>
    @endauth
</head>
<body class="{{ $bodyClass ?? 'bg-slate-100 min-h-screen antialiased' }}">
    @auth
        <x-sidebar />
        <div id="sb-overlay" onclick="sbMobileToggle()"></div>
        <div id="mobile-topbar" style="position:sticky;top:0;z-index:40;background:#0f172a;padding:10px 16px;align-items:center;gap:12px;box-shadow:0 1px 3px rgba(0,0,0,0.3);">
            <button onclick="sbMobileToggle()" style="color:#94a3b8;background:none;border:none;cursor:pointer;padding:4px;display:flex;line-height:0;">
                <svg style="width:20px;height:20px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
                </svg>
            </button>
            <a href="{{ route('dashboard') }}" style="line-height:0;">
                <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" style="height:28px;width:auto;">
            </a>
        </div>
        <div id="page-content">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endauth
</body>
</html>
