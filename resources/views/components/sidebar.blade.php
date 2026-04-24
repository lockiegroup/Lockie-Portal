@php
    $isPrintSection = request()->routeIs('print.*');
    $user           = auth()->user();
    $initials       = $user ? strtoupper(mb_substr($user->name ?? $user->email, 0, 2)) : '??';
@endphp

<aside id="sidebar">

    {{-- Logo / Brand --}}
    <div style="padding:14px 12px;border-bottom:1px solid #1e293b;display:flex;align-items:center;justify-content:space-between;flex-shrink:0;">
        <a href="{{ route('dashboard') }}" style="display:flex;align-items:center;gap:10px;min-width:0;text-decoration:none;overflow:hidden;">
            <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" style="height:30px;width:auto;flex-shrink:0;">
            <span class="sb-label" style="color:white;font-weight:700;font-size:0.875rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Portal</span>
        </a>
        <button onclick="sbToggle()" title="Collapse sidebar"
            style="color:#475569;background:none;border:none;cursor:pointer;padding:6px;border-radius:6px;line-height:0;flex-shrink:0;transition:color 0.15s;"
            onmouseover="this.style.color='#94a3b8'" onmouseout="this.style.color='#475569'">
            <svg id="sb-chevron" style="width:15px;height:15px;transition:transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                <polyline points="15 18 9 12 15 6"/>
            </svg>
        </button>
    </div>

    {{-- Navigation --}}
    <nav style="flex:1;padding:10px 8px;overflow-y:auto;overflow-x:hidden;scrollbar-width:none;-ms-overflow-style:none;" class="sb-nav">

        <a href="{{ route('dashboard') }}"
           class="sb-item{{ request()->routeIs('dashboard') ? ' sb-active' : '' }}"
           data-tip="Dashboard">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/>
                <rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/>
            </svg>
            <span class="sb-label">Dashboard</span>
        </a>

        @if($user->hasModule('sales'))
        <a href="{{ route('sales') }}"
           class="sb-item{{ request()->routeIs('sales*') ? ' sb-active' : '' }}"
           data-tip="Sales">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            <span class="sb-label">Sales</span>
        </a>
        @endif

        @if($user->hasModule('stock'))
        <a href="{{ route('stock.index') }}"
           class="sb-item{{ request()->routeIs('stock.*') ? ' sb-active' : '' }}"
           data-tip="Stock Overview">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
            </svg>
            <span class="sb-label">Stock Overview</span>
        </a>
        @endif

        @can('cash_flow')
        <a href="{{ route('cash-flow.index') }}"
           class="sb-item{{ request()->routeIs('cash-flow.*') ? ' sb-active' : '' }}"
           data-tip="Cash Flow">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="12" y1="1" x2="12" y2="23"/>
                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
            </svg>
            <span class="sb-label">Cash Flow</span>
        </a>
        @endcan

        <div style="height:1px;background:#1e293b;margin:10px 4px 8px;"></div>
        <p class="sb-section" style="font-size:0.625rem;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.1em;padding:0 10px;margin-bottom:6px;">Operations</p>

        @if($user->hasModule('health_safety'))
        <a href="{{ route('hs.actions.index') }}"
           class="sb-item{{ request()->routeIs('hs.*') ? ' sb-active' : '' }}"
           data-tip="Health &amp; Safety">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            <span class="sb-label">Health &amp; Safety</span>
        </a>
        @endif

        @if($user->hasModule('envelopes'))
        <a href="{{ route('church-envelopes.index') }}"
           class="sb-item{{ request()->routeIs('church-envelopes.*') ? ' sb-active' : '' }}"
           data-tip="Church Envelopes">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span class="sb-label">Church Envelopes</span>
        </a>
        @endif

        @if($user->hasModule('policies'))
        <a href="{{ route('policies.index') }}"
           class="sb-item{{ request()->routeIs('policies.*') ? ' sb-active' : '' }}"
           data-tip="Policies">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            <span class="sb-label">Policies</span>
        </a>
        @endif

        @if($user->hasModule('print_schedule'))
        <a href="{{ route('print.index') }}"
           class="sb-item{{ $isPrintSection ? ' sb-active' : '' }}"
           data-tip="Print Schedule">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <polyline points="6 9 6 2 18 2 18 9"/>
                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                <rect x="6" y="14" width="12" height="8"/>
            </svg>
            <span class="sb-label">Print Schedule</span>
        </a>

        @if($isPrintSection)
        <div class="sb-sub-group">
            <a href="{{ route('print.overview') }}"
               class="sb-sub-item{{ request()->routeIs('print.overview') ? ' sb-active' : '' }}">Overview</a>
            <a href="{{ route('print.index') }}"
               class="sb-sub-item{{ request()->routeIs('print.index') ? ' sb-active' : '' }}">Schedule</a>
            <a href="{{ route('print.archive') }}"
               class="sb-sub-item{{ request()->routeIs('print.archive') ? ' sb-active' : '' }}">Archive</a>
        </div>
        @endif
        @endif

        @canany(['manage_users', 'print_settings', 'envelope_settings', 'policy_settings', 'cash_flow'])
        <div style="height:1px;background:#1e293b;margin:10px 4px 8px;"></div>
        <p class="sb-section" style="font-size:0.625rem;font-weight:700;color:#334155;text-transform:uppercase;letter-spacing:0.1em;padding:0 10px;margin-bottom:6px;">Admin</p>

        @can('manage_users')
        <a href="{{ route('admin.users.index') }}"
           class="sb-item{{ request()->routeIs('admin.users*') ? ' sb-active' : '' }}"
           data-tip="Manage Users">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            <span class="sb-label">Manage Users</span>
        </a>
        <a href="{{ route('admin.activity-log') }}"
           class="sb-item{{ request()->routeIs('admin.activity-log') ? ' sb-active' : '' }}"
           data-tip="Activity Log">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                <polyline points="10 9 9 9 8 9"/>
            </svg>
            <span class="sb-label">Activity Log</span>
        </a>
        @endcan

        @can('print_settings')
        <a href="{{ route('admin.print-settings.index') }}"
           class="sb-item{{ request()->routeIs('admin.print-settings*') ? ' sb-active' : '' }}"
           data-tip="Print Settings">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="3"/>
                <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
            </svg>
            <span class="sb-label">Print Settings</span>
        </a>
        @endcan

        @can('policy_settings')
        <a href="{{ route('admin.policies.index') }}"
           class="sb-item{{ request()->routeIs('admin.policies*') ? ' sb-active' : '' }}"
           data-tip="Policy Settings">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
            </svg>
            <span class="sb-label">Policy Settings</span>
        </a>
        @endcan

        @can('envelope_settings')
        <a href="{{ route('admin.envelope-settings.index') }}"
           class="sb-item{{ request()->routeIs('admin.envelope-settings*') ? ' sb-active' : '' }}"
           data-tip="Envelope Settings">
            <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
            <span class="sb-label">Envelope Settings</span>
        </a>
        @endcan

        @endcanany

    </nav>

    {{-- User footer --}}
    <div style="border-top:1px solid #1e293b;padding:10px 8px;flex-shrink:0;">
        <div id="sb-user-info" style="display:flex;align-items:center;gap:10px;padding:6px 10px;margin-bottom:4px;">
            <div style="width:28px;height:28px;border-radius:50%;background:#1e293b;border:1px solid #334155;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:0.65rem;font-weight:700;flex-shrink:0;">{{ $initials }}</div>
            <div class="sb-label" style="min-width:0;overflow:hidden;">
                @if($user?->name)
                    <p style="color:#e2e8f0;font-size:0.75rem;font-weight:600;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $user->name }}</p>
                @endif
                <p style="color:#475569;font-size:0.7rem;line-height:1.4;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $user?->email }}</p>
            </div>
        </div>
        <form action="{{ route('logout') }}" method="POST" style="margin:0;">
            @csrf
            <button type="submit" class="sb-item" style="width:100%;background:none;border:none;cursor:pointer;font-family:inherit;" data-tip="Sign out">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                    <polyline points="16 17 21 12 16 7"/>
                    <line x1="21" y1="12" x2="9" y2="12"/>
                </svg>
                <span class="sb-label">Sign out</span>
            </button>
        </form>
    </div>

</aside>

<script>
(function () {
    var KEY = 'sb_c';
    function apply() {
        var c = localStorage.getItem(KEY) === '1';
        document.body.classList.toggle('sb-collapsed', c);
        var ch = document.getElementById('sb-chevron');
        if (ch) ch.style.transform = c ? 'rotate(180deg)' : '';
    }
    window.sbToggle       = function () { localStorage.setItem(KEY, localStorage.getItem(KEY) === '1' ? '0' : '1'); apply(); };
    window.sbMobileToggle = function () { document.body.classList.toggle('sb-open'); };
    apply();
})();
</script>
