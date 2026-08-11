@php
    $isPrintSection     = request()->routeIs('print.*');
    $isWatchlistSection = request()->routeIs('stock-watchlist.*');
    $user               = auth()->user();
    $initials           = $user ? strtoupper(mb_substr($user->name ?? $user->email, 0, 2)) : '??';

    $activeFinance    = request()->routeIs('sales*') || request()->routeIs('amazon.*');
    $activeStock      = request()->routeIs('stock.*') || $isWatchlistSection;
    $activePlanning   = request()->routeIs('key-actions.*') || request()->routeIs('action-plans.*');
    $activeCustomers  = request()->routeIs('key-accounts.*') || request()->routeIs('crm.*') || request()->routeIs('reminders.*');
    $activeOperations = request()->routeIs('church-envelopes.*') || request()->routeIs('policies.*') || request()->routeIs('training.*') || request()->routeIs('letter-filter.*') || $isPrintSection;
    $activeAdmin      = request()->routeIs('admin.*') || request()->routeIs('imports.*');

    $showKeyActions  = $user->isMaster() || $user->can('admin') || \App\Models\KeyActionGroup::whereHas('members', fn($q) => $q->where('user_id', $user->id))->exists();
    $showActionPlans = $user->isMaster() || \App\Models\ActionPlan::whereHas('members', fn($q) => $q->where('user_id', $user->id))->exists();
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

        {{-- FINANCE --}}
        @if($user->hasModule('sales') || $user->hasModule('amazon'))
        <div style="height:1px;background:#1e293b;margin:10px 4px 2px;"></div>
        <button onclick="sbSection('finance')" class="sb-section-btn sb-label" data-tip="Finance">
            <span style="font-size:0.625rem;font-weight:700;text-transform:uppercase;">Finance</span>
            <svg id="sc-finance" style="width:10px;height:10px;flex-shrink:0;transition:transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="ss-finance">
            @if($user->hasModule('sales'))
            <a href="{{ route('sales') }}" class="sb-item{{ request()->routeIs('sales*') ? ' sb-active' : '' }}" data-tip="Sales">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                <span class="sb-label">Sales</span>
            </a>
            @endif
            @if($user->hasModule('amazon'))
            <a href="{{ route('amazon.index') }}" class="sb-item{{ request()->routeIs('amazon.*') ? ' sb-active' : '' }}" data-tip="Amazon &amp; Xero">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                    <line x1="7" y1="7" x2="7.01" y2="7"/>
                </svg>
                <span class="sb-label">Amazon &amp; Xero</span>
            </a>
            @endif
        </div>
        @endif

        {{-- STOCK --}}
        @if($user->hasModule('stock') || $user->can('stock_ordering'))
        <div style="height:1px;background:#1e293b;margin:10px 4px 2px;"></div>
        <button onclick="sbSection('stock')" class="sb-section-btn sb-label" data-tip="Stock">
            <span style="font-size:0.625rem;font-weight:700;text-transform:uppercase;">Stock</span>
            <svg id="sc-stock" style="width:10px;height:10px;flex-shrink:0;transition:transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="ss-stock">
            @if($user->hasModule('stock'))
            <a href="{{ route('stock.index') }}" class="sb-item{{ request()->routeIs('stock.*') ? ' sb-active' : '' }}" data-tip="Stock Overview">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/>
                </svg>
                <span class="sb-label">Stock Overview</span>
            </a>
            @endif
            @can('stock_ordering')
            <a href="{{ route('stock-watchlist.index') }}" class="sb-item{{ $isWatchlistSection ? ' sb-active' : '' }}" data-tip="Stock Watchlist">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M3 15h18M9 3v18"/>
                </svg>
                <span class="sb-label">Stock Watchlist</span>
            </a>
            @endcan
        </div>
        @endif

        {{-- PLANNING --}}
        @if($showKeyActions || $showActionPlans)
        <div style="height:1px;background:#1e293b;margin:10px 4px 2px;"></div>
        <button onclick="sbSection('planning')" class="sb-section-btn sb-label" data-tip="Planning">
            <span style="font-size:0.625rem;font-weight:700;text-transform:uppercase;">Planning</span>
            <svg id="sc-planning" style="width:10px;height:10px;flex-shrink:0;transition:transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="ss-planning">
            @if($showKeyActions)
            <a href="{{ route('key-actions.index') }}" class="sb-item{{ request()->routeIs('key-actions.*') ? ' sb-active' : '' }}" data-tip="Key Actions">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span class="sb-label">Key Actions</span>
            </a>
            @endif
            @if($showActionPlans)
            <a href="{{ route('action-plans.index') }}" class="sb-item{{ request()->routeIs('action-plans.*') ? ' sb-active' : '' }}" data-tip="Action Plans">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/>
                </svg>
                <span class="sb-label">Action Plans</span>
            </a>
            @endif
        </div>
        @endif

        {{-- CUSTOMERS --}}
        @if($user->hasModule('key_accounts') || $user->hasModule('crm') || $user->can('reminders'))
        <div style="height:1px;background:#1e293b;margin:10px 4px 2px;"></div>
        <button onclick="sbSection('customers')" class="sb-section-btn sb-label" data-tip="Customers">
            <span style="font-size:0.625rem;font-weight:700;text-transform:uppercase;">Customers</span>
            <svg id="sc-customers" style="width:10px;height:10px;flex-shrink:0;transition:transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="ss-customers">
            @if($user->hasModule('key_accounts'))
            <a href="{{ route('key-accounts.index') }}" class="sb-item{{ request()->routeIs('key-accounts.*') ? ' sb-active' : '' }}" data-tip="Key Accounts">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                    <path d="M21 21v-2a4 4 0 0 0-3-3.87"/>
                </svg>
                <span class="sb-label">Key Accounts</span>
            </a>
            @endif
            @if($user->hasModule('crm'))
            <a href="{{ route('crm.index') }}" class="sb-item{{ request()->routeIs('crm.*') ? ' sb-active' : '' }}" data-tip="Customer Insights">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                </svg>
                <span class="sb-label">Customer Insights</span>
            </a>
            @endif
            @can('reminders')
            <a href="{{ route('reminders.index') }}" class="sb-item{{ request()->routeIs('reminders.*') ? ' sb-active' : '' }}" data-tip="Reminders">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                </svg>
                <span class="sb-label">Reminders</span>
            </a>
            @endcan
        </div>
        @endif

        {{-- OPERATIONS --}}
        @if($user->hasModule('envelopes') || $user->hasModule('policies') || $user->can('factory_training_view') || $user->hasModule('print_schedule'))
        <div style="height:1px;background:#1e293b;margin:10px 4px 2px;"></div>
        <button onclick="sbSection('operations')" class="sb-section-btn sb-label" data-tip="Operations">
            <span style="font-size:0.625rem;font-weight:700;text-transform:uppercase;">Operations</span>
            <svg id="sc-operations" style="width:10px;height:10px;flex-shrink:0;transition:transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="ss-operations">
            @if($user->hasModule('envelopes'))
            <a href="{{ route('church-envelopes.index') }}" class="sb-item{{ request()->routeIs('church-envelopes.*') ? ' sb-active' : '' }}" data-tip="Church Envelopes">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <span class="sb-label">Church Envelopes</span>
            </a>
            @endif
            @if($user->hasModule('envelopes'))
            <a href="{{ route('letter-filter.index') }}" class="sb-item{{ request()->routeIs('letter-filter.*') ? ' sb-active' : '' }}" data-tip="Letter Filter">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="9" y1="13" x2="15" y2="13"/>
                    <line x1="9" y1="17" x2="13" y2="17"/>
                </svg>
                <span class="sb-label">Letter Filter</span>
            </a>
            @endif
            @if($user->hasModule('policies'))
            <a href="{{ route('policies.index') }}" class="sb-item{{ request()->routeIs('policies.*') ? ' sb-active' : '' }}" data-tip="Policies">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                <span class="sb-label">Policies</span>
            </a>
            @endif
            @can('factory_training_view')
            <a href="{{ route('training.index') }}" class="sb-item{{ request()->routeIs('training.*') ? ' sb-active' : '' }}" data-tip="Factory Training">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                <span class="sb-label">Training</span>
            </a>
            @endcan
            @if($user->hasModule('print_schedule'))
            <button onclick="togglePrint()" id="print-toggle" class="sb-item{{ $isPrintSection ? ' sb-active' : '' }}" style="width:100%;background:none;border:none;cursor:pointer;font-family:inherit;text-align:left;" data-tip="Print Schedule">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                <span class="sb-label" style="flex:1;">Print Schedule</span>
                <svg id="print-chevron" class="sb-label" style="width:13px;height:13px;flex-shrink:0;transition:transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div id="print-sub" class="sb-label sb-sub-group" style="display:none;">
                <a href="{{ route('print.production') }}" class="sb-sub-item{{ request()->routeIs('print.production*') ? ' sb-active' : '' }}">Dashboard</a>
                <a href="{{ route('print.overview') }}" class="sb-sub-item{{ request()->routeIs('print.overview') ? ' sb-active' : '' }}">Overview</a>
                <a href="{{ route('print.index') }}" class="sb-sub-item{{ request()->routeIs('print.index') ? ' sb-active' : '' }}">Schedule</a>
                <a href="{{ route('print.archive') }}" class="sb-sub-item{{ request()->routeIs('print.archive') ? ' sb-active' : '' }}">Archive</a>
                @can('print_settings')
                <a href="{{ route('print.machine-log') }}" class="sb-sub-item{{ request()->routeIs('print.machine-log') ? ' sb-active' : '' }}">Machine Log</a>
                <a href="{{ route('print.analytics') }}" class="sb-sub-item{{ request()->routeIs('print.analytics') ? ' sb-active' : '' }}">Analytics</a>
                @endcan
            </div>
            @endif
        </div>
        @endif

        {{-- ADMIN --}}
        @canany(['manage_users', 'print_settings', 'envelope_settings', 'policy_settings', 'key_accounts_admin', 'imports'])
        <div style="height:1px;background:#1e293b;margin:10px 4px 2px;"></div>
        <button onclick="sbSection('admin')" class="sb-section-btn sb-label" data-tip="Admin">
            <span style="font-size:0.625rem;font-weight:700;text-transform:uppercase;">Admin</span>
            <svg id="sc-admin" style="width:10px;height:10px;flex-shrink:0;transition:transform 0.2s;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
        </button>
        <div id="ss-admin">
            @can('manage_users')
            <a href="{{ route('admin.users.index') }}" class="sb-item{{ request()->routeIs('admin.users*') ? ' sb-active' : '' }}" data-tip="Manage Users">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
                <span class="sb-label">Manage Users</span>
            </a>
            <a href="{{ route('admin.activity-log') }}" class="sb-item{{ request()->routeIs('admin.activity-log') ? ' sb-active' : '' }}" data-tip="Activity Log">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                    <polyline points="10 9 9 9 8 9"/>
                </svg>
                <span class="sb-label">Activity Log</span>
            </a>
            @endcan
            @can('imports')
            <a href="{{ route('imports.index') }}" class="sb-item{{ request()->routeIs('imports.*') ? ' sb-active' : '' }}" data-tip="Imports">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <polyline points="7 10 12 15 17 10"/>
                    <line x1="12" y1="15" x2="12" y2="3"/>
                </svg>
                <span class="sb-label">Imports</span>
            </a>
            @endcan
            @can('print_settings')
            <a href="{{ route('admin.print-settings.index') }}" class="sb-item{{ request()->routeIs('admin.print-settings*') ? ' sb-active' : '' }}" data-tip="Print Settings">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                <span class="sb-label">Print Settings</span>
            </a>
            @endcan
            @can('policy_settings')
            <a href="{{ route('admin.policies.index') }}" class="sb-item{{ request()->routeIs('admin.policies*') ? ' sb-active' : '' }}" data-tip="Policy Settings">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                    <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                </svg>
                <span class="sb-label">Policy Settings</span>
            </a>
            @endcan
            @can('key_accounts_admin')
            <a href="{{ route('admin.key-accounts.index') }}" class="sb-item{{ request()->routeIs('admin.key-accounts*') ? ' sb-active' : '' }}" data-tip="Key Accounts Admin">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <span class="sb-label">Key Accounts</span>
            </a>
            @endcan
            @can('envelope_settings')
            <a href="{{ route('admin.envelope-settings.index') }}" class="sb-item{{ request()->routeIs('admin.envelope-settings*') ? ' sb-active' : '' }}" data-tip="Envelope Settings">
                <svg class="sb-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                    <polyline points="22,6 12,13 2,6"/>
                </svg>
                <span class="sb-label">Envelope Settings</span>
            </a>
            @endcan
        </div>
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

<style>
.sb-section-btn {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    background: none;
    border: none;
    cursor: pointer;
    padding: 4px 10px 5px;
    border-radius: 6px;
    font-family: inherit;
    margin-bottom: 2px;
    transition: background 0.15s;
}
.sb-section-btn:hover {
    background: rgba(255,255,255,0.06);
}
.sb-section-btn span {
    color: #64748b !important;
    letter-spacing: 0.08em;
}
.sb-section-btn svg {
    color: #475569 !important;
}
.sb-section-btn:hover span,
.sb-section-btn:hover svg {
    color: #94a3b8 !important;
}
</style>

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

    // Section collapsing
    var activeSections = {
        finance:    {{ $activeFinance    ? 'true' : 'false' }},
        stock:      {{ $activeStock      ? 'true' : 'false' }},
        planning:   {{ $activePlanning   ? 'true' : 'false' }},
        customers:  {{ $activeCustomers  ? 'true' : 'false' }},
        operations: {{ $activeOperations ? 'true' : 'false' }},
        admin:      {{ $activeAdmin      ? 'true' : 'false' }},
    };

    function sectionOpen(name) {
        var saved = localStorage.getItem('sb_sec_' + name);
        // Always open if active route is in this section
        if (activeSections[name]) return true;
        // Use saved state; default collapsed
        if (saved === null) return false;
        return saved === '1';
    }

    window.sbSection = function (name) {
        var el = document.getElementById('ss-' + name);
        var ch = document.getElementById('sc-' + name);
        if (!el) return;
        var isOpen = el.style.display !== 'none';
        el.style.display = isOpen ? 'none' : 'block';
        if (ch) ch.style.transform = isOpen ? 'rotate(-90deg)' : '';
        localStorage.setItem('sb_sec_' + name, isOpen ? '0' : '1');
    };

    // Apply initial states
    ['finance', 'stock', 'planning', 'customers', 'operations', 'admin'].forEach(function (name) {
        var el = document.getElementById('ss-' + name);
        var ch = document.getElementById('sc-' + name);
        if (!el) return;
        var open = sectionOpen(name);
        el.style.display = open ? 'block' : 'none';
        if (ch) ch.style.transform = open ? '' : 'rotate(-90deg)';
    });

    // Stock Watchlist accordion
    var isWatchlist = {{ $isWatchlistSection ? 'true' : 'false' }};
    var watchlistOpen = isWatchlist || localStorage.getItem('watchlist_open') === '1';
    window.toggleWatchlist = function () {
        var sub = document.getElementById('watchlist-sub');
        var ch  = document.getElementById('watchlist-chevron');
        if (!sub) return;
        var nowOpen = sub.style.display !== 'none';
        sub.style.display = nowOpen ? 'none' : 'block';
        if (ch) ch.style.transform = nowOpen ? '' : 'rotate(180deg)';
        localStorage.setItem('watchlist_open', nowOpen ? '0' : '1');
    };
    (function () {
        var sub = document.getElementById('watchlist-sub');
        var ch  = document.getElementById('watchlist-chevron');
        if (!sub) return;
        if (watchlistOpen) {
            sub.style.display = 'block';
            if (ch) ch.style.transform = 'rotate(180deg)';
        }
    })();

    // Print Schedule accordion
    var isPrint = {{ $isPrintSection ? 'true' : 'false' }};
    var printOpen = isPrint || localStorage.getItem('print_open') === '1';
    window.togglePrint = function () {
        var sub = document.getElementById('print-sub');
        var ch  = document.getElementById('print-chevron');
        if (!sub) return;
        var nowOpen = sub.style.display !== 'none';
        sub.style.display = nowOpen ? 'none' : 'block';
        if (ch) ch.style.transform = nowOpen ? '' : 'rotate(180deg)';
        localStorage.setItem('print_open', nowOpen ? '0' : '1');
    };
    (function () {
        var sub = document.getElementById('print-sub');
        var ch  = document.getElementById('print-chevron');
        if (!sub) return;
        if (printOpen) {
            sub.style.display = 'block';
            if (ch) ch.style.transform = 'rotate(180deg)';
        }
    })();
})();
</script>
