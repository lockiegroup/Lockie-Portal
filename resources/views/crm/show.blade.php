<x-layout title="{{ $customer }} — CRM — Lockie Portal">
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    {{-- Back --}}
    <div style="margin-bottom:1.5rem;">
        <a href="{{ route('crm.index', ['warehouse' => $warehouse]) }}"
           style="font-size:0.875rem;color:#94a3b8;text-decoration:none;">&#8592; Customer Insights</a>
    </div>

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:2rem;">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $customer ?: $customerCode }}</h1>
            <p style="font-size:0.875rem;color:#94a3b8;margin-top:3px;">
                {{ $customerCode }}
                @if($customerType) &bull; {{ $customerType }} @endif
                @if($keyAccount)
                    &bull; <a href="{{ route('key-accounts.show', $keyAccount) }}" style="color:#6366f1;text-decoration:none;">View Key Account &rarr;</a>
                @endif
            </p>
        </div>

        {{-- Warehouse filter --}}
        @if($warehouses->count() > 1)
        <form method="GET" style="display:flex;gap:8px;align-items:center;">
            <select name="warehouse" onchange="this.form.submit()"
                style="padding:7px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;">
                <option value="">All Warehouses</option>
                @foreach($warehouses as $w)
                    <option value="{{ $w }}" {{ $warehouse === $w ? 'selected' : '' }}>{{ $w }}</option>
                @endforeach
            </select>
        </form>
        @endif
    </div>

    {{-- KPI strip --}}
    @php
        $pct = $totalPrev12 > 0 ? (($total12m - $totalPrev12) / $totalPrev12) * 100 : null;
        $pctColour = ($pct !== null && $pct >= 5) ? '#16a34a' : (($pct !== null && $pct <= -5) ? '#dc2626' : '#64748b');
        $lastOrderDate = $lastOrder ? \Carbon\Carbon::parse($lastOrder) : null;
        $daysSince = $lastOrderDate ? $lastOrderDate->diffInDays(now()) : null;
    @endphp
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:2.5rem;">

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Last 12 Months</p>
            <p style="font-size:1.625rem;font-weight:700;color:#1e293b;line-height:1;">£{{ number_format($total12m, 0) }}</p>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Prev 12 Months</p>
            <p style="font-size:1.625rem;font-weight:700;color:#64748b;line-height:1;">
                {{ $totalPrev12 > 0 ? '£' . number_format($totalPrev12, 0) : '—' }}
            </p>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Year-on-Year</p>
            <p style="font-size:1.625rem;font-weight:700;color:{{ $pctColour }};line-height:1;">
                @if($pct !== null)
                    {{ $pct > 0 ? '+' : '' }}{{ number_format($pct, 1) }}%
                @else
                    —
                @endif
            </p>
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Last Order</p>
            @if($lastOrderDate)
                <p style="font-size:1rem;font-weight:600;color:{{ $daysSince > 180 ? '#dc2626' : ($daysSince > 90 ? '#d97706' : '#1e293b') }};line-height:1.3;">
                    {{ $lastOrderDate->format('d M Y') }}
                </p>
                <p style="font-size:0.75rem;color:#94a3b8;margin-top:2px;">{{ $lastOrderDate->diffForHumans() }}</p>
            @else
                <p style="font-size:1rem;color:#94a3b8;">—</p>
            @endif
        </div>

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.125rem 1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            @php
                $nextDaysUntil = $expectedNext ? now()->diffInDays($expectedNext, false) : null;
                $nextColour = ($nextDaysUntil !== null && $nextDaysUntil < 0) ? '#dc2626'
                    : (($nextDaysUntil !== null && $nextDaysUntil <= 14) ? '#d97706' : '#1e293b');
            @endphp
            <p style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:6px;">Expected Next Order</p>
            @if($expectedNext)
                <p style="font-size:1rem;font-weight:600;color:{{ $nextColour }};line-height:1.3;">
                    {{ $expectedNext->format('d M Y') }}
                </p>
                <p style="font-size:0.75rem;color:#94a3b8;margin-top:2px;">
                    @if($nextDaysUntil < 0)
                        {{ abs((int) $nextDaysUntil) }}d overdue
                    @else
                        in {{ (int) $nextDaysUntil }}d
                    @endif
                    &bull; orders every ~{{ $avgDays }}d
                </p>
            @else
                <p style="font-size:0.875rem;color:#94a3b8;">Not enough data</p>
            @endif
        </div>

    </div>

    {{-- Yearly breakdown --}}
    <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.875rem;">Annual Spend</h2>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);margin-bottom:2.5rem;">
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table style="width:100%;min-width:420px;border-collapse:collapse;font-size:0.875rem;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                    <th style="padding:9px 16px;text-align:left;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Year</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Q1</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Q2</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Q3</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Q4</th>
                    <th style="padding:9px 16px;text-align:right;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byYear as $year => $qs)
                    <tr style="border-bottom:1px solid #f1f5f9;">
                        <td style="padding:10px 16px;font-weight:600;color:#1e293b;">{{ $year }}</td>
                        @foreach(['q1','q2','q3','q4'] as $q)
                            <td style="padding:10px 16px;text-align:right;color:{{ isset($qs[$q]) && $qs[$q] > 0 ? '#334155' : '#cbd5e1' }};">
                                {{ isset($qs[$q]) && $qs[$q] > 0 ? '£' . number_format($qs[$q], 0) : '—' }}
                            </td>
                        @endforeach
                        <td style="padding:10px 16px;text-align:right;font-weight:600;color:#1e293b;">
                            £{{ number_format($qs['total'] ?? 0, 0) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:2.5rem;">

        {{-- Top products --}}
        <div>
            <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.875rem;">Top Products</h2>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                    <tbody>
                        @foreach($topProducts as $p)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:10px 14px;">
                                    <p style="font-weight:600;color:#1e293b;">{{ $p['product_code'] }}</p>
                                    @if($p['description'] && $p['description'] !== $p['product_code'])
                                        <p style="font-size:0.75rem;color:#94a3b8;">{{ $p['description'] }}</p>
                                    @endif
                                </td>
                                <td style="padding:10px 14px;text-align:right;font-weight:600;color:#334155;">
                                    £{{ number_format($p['total'], 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Recent orders --}}
        <div>
            <h2 style="font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.875rem;">Recent Orders</h2>
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
                <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                    <tbody>
                        @foreach($recentOrders as $o)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:10px 14px;">
                                    <p style="font-weight:600;color:#1e293b;">{{ $o['order_no'] ?: '—' }}</p>
                                    <p style="font-size:0.75rem;color:#94a3b8;">
                                        {{ \Carbon\Carbon::parse($o['date'])->format('d M Y') }}
                                        @if($o['warehouse']) &bull; {{ $o['warehouse'] }} @endif
                                    </p>
                                </td>
                                <td style="padding:10px 14px;text-align:right;font-weight:600;color:#334155;">
                                    £{{ number_format($o['total'], 0) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Key / Growth Account sections --}}
    @php
        // Use CRM-owned routes when no Key Account record exists yet
        $notesAction   = $keyAccount ? route('key-accounts.notes.update', $keyAccount) : route('crm.notes.update', $customerCode);
        $contactAction = $keyAccount ? route('key-accounts.contacts.store', $keyAccount) : route('crm.contacts.store', $customerCode);
    @endphp

    @if(session('crm_success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;font-size:0.875rem;border-radius:8px;padding:10px 14px;margin-bottom:1.25rem;">
            {{ session('crm_success') }}
        </div>
    @endif

    @if($keyAccount && $keyAccount->user_id)
        {{-- Account badge + manager --}}
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:1.5rem;">
            <span style="display:inline-block;padding:3px 10px;border-radius:999px;font-size:0.8125rem;font-weight:600;
                         background:{{ $keyAccount->type === 'key' ? '#e0f2fe' : '#d1fae5' }};
                         color:{{ $keyAccount->type === 'key' ? '#0369a1' : '#059669' }};">
                {{ $keyAccount->type === 'key' ? 'Key Account' : 'Growth Account' }}
            </span>
            <span style="font-size:0.875rem;color:#64748b;">
                Account manager: <strong style="color:#334155;">{{ $keyAccount->user->name }}</strong>
            </span>
            <a href="{{ route('key-accounts.show', $keyAccount) }}"
               style="margin-left:auto;font-size:0.8125rem;color:#64748b;text-decoration:none;">
                View in Key Accounts &rarr;
            </a>
        </div>
    @endif

        {{-- Notes --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.5rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h2 style="font-size:0.875rem;font-weight:600;color:#1e293b;margin-bottom:0.875rem;">Notes</h2>
            <form action="{{ $notesAction }}" method="POST">
                @csrf @method('PATCH')
                <textarea name="notes" rows="3" placeholder="Any notes about this account…"
                    style="width:100%;padding:10px 14px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#1e293b;resize:none;box-sizing:border-box;">{{ old('notes', $keyAccount?->notes) }}</textarea>
                <div style="margin-top:8px;display:flex;justify-content:flex-end;">
                    <button type="submit"
                        style="padding:7px 18px;background:#1e293b;color:#fff;border:none;border-radius:8px;font-size:0.875rem;font-weight:500;cursor:pointer;">
                        Save Notes
                    </button>
                </div>
            </form>
        </div>

        {{-- Log contact --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.5rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h2 style="font-size:0.875rem;font-weight:600;color:#1e293b;margin-bottom:0.875rem;">Log Contact</h2>
            <form action="{{ $contactAction }}" method="POST">
                @csrf
                @if($errors->has('contacted_at') || $errors->has('note'))
                    <div style="background:#fef2f2;border:1px solid #fecaca;color:#dc2626;font-size:0.8125rem;border-radius:8px;padding:8px 12px;margin-bottom:10px;">
                        {{ $errors->first() }}
                    </div>
                @endif
                <div style="display:grid;grid-template-columns:180px 1fr;gap:12px;align-items:start;">
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:500;color:#475569;margin-bottom:5px;">Date</label>
                        <input type="date" name="contacted_at" value="{{ old('contacted_at', now()->format('Y-m-d')) }}" required
                            style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:500;color:#475569;margin-bottom:5px;">Note</label>
                        <textarea name="note" rows="2" required placeholder="What was discussed or actioned…"
                            style="width:100%;padding:8px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;resize:none;box-sizing:border-box;">{{ old('note') }}</textarea>
                    </div>
                </div>
                <div style="margin-top:10px;display:flex;justify-content:flex-end;">
                    <button type="submit"
                        style="padding:7px 18px;background:#1e293b;color:#fff;border:none;border-radius:8px;font-size:0.875rem;font-weight:500;cursor:pointer;">
                        Log Contact
                    </button>
                </div>
            </form>
        </div>

        {{-- Contact history --}}
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.5rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h2 style="font-size:0.875rem;font-weight:600;color:#1e293b;margin-bottom:0.875rem;">Contact History</h2>
            @if(!$keyAccount || $keyAccount->contacts->isEmpty())
                <p style="font-size:0.875rem;color:#94a3b8;">No contacts logged yet.</p>
            @else
                <div style="display:flex;flex-direction:column;gap:0;">
                    @foreach($keyAccount->contacts as $contact)
                        <div style="display:flex;align-items:flex-start;gap:16px;padding:10px 0;border-bottom:1px solid #f1f5f9;">
                            <span style="font-size:0.75rem;color:#94a3b8;white-space:nowrap;padding-top:2px;width:80px;flex-shrink:0;">
                                {{ $contact->contacted_at->format('d M Y') }}
                            </span>
                            <span style="font-size:0.875rem;color:#334155;flex:1;">{{ $contact->note }}</span>
                            <span style="font-size:0.75rem;color:#94a3b8;white-space:nowrap;flex-shrink:0;">{{ $contact->user?->name ?? '—' }}</span>
                            <form action="{{ $keyAccount ? route('key-accounts.contacts.destroy', [$keyAccount, $contact]) : route('crm.contacts.destroy', [$customerCode, $contact]) }}" method="POST"
                                onsubmit="return confirm('Remove this contact entry?')" style="margin:0;">
                                @csrf @method('DELETE')
                                <button type="submit" style="background:none;border:none;color:#cbd5e1;font-size:1.125rem;cursor:pointer;line-height:1;padding:0;" onmouseover="this.style.color='#ef4444'" onmouseout="this.style.color='#cbd5e1'">&times;</button>
                            </form>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Gift history (only exists if there's a Key Account record with gifts) --}}
        @if($keyAccount && $keyAccount->gifts->isNotEmpty())
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:0.875rem;padding:1.5rem;margin-bottom:1.25rem;box-shadow:0 1px 3px rgba(0,0,0,0.04);">
            <h2 style="font-size:0.875rem;font-weight:600;color:#1e293b;margin-bottom:0.875rem;">Gift History</h2>
            <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                <thead>
                    <tr style="border-bottom:1px solid #e2e8f0;">
                        <th style="text-align:left;padding-bottom:8px;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Date</th>
                        <th style="text-align:left;padding-bottom:8px;padding-left:16px;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Recipient</th>
                        <th style="text-align:left;padding-bottom:8px;padding-left:16px;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Gift</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($keyAccount->gifts as $gift)
                        <tr style="border-bottom:1px solid #f1f5f9;">
                            <td style="padding:8px 0;color:#64748b;white-space:nowrap;">{{ $gift->gifted_at->format('d M Y') }}</td>
                            <td style="padding:8px 0 8px 16px;color:#334155;">{{ $gift->recipient }}</td>
                            <td style="padding:8px 0 8px 16px;color:#334155;">{{ $gift->description }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

</main>
</x-layout>
