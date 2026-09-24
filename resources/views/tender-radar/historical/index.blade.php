<x-layout title="Contract History — Lockie Portal">
<main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <div style="margin-bottom:1.25rem;">
        <a href="{{ route('tender-radar.index') }}" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">← Tender Radar</a>
    </div>

    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Contract History</h1>
            <p class="text-sm text-slate-500 mt-1">Historical contracts to monitor for replacement procurement.</p>
        </div>
        <a href="{{ route('tender-radar.historical.create') }}"
            style="display:inline-flex;align-items:center;gap:6px;font-size:0.875rem;padding:8px 16px;border-radius:8px;background:#1e293b;color:#fff;text-decoration:none;white-space:nowrap;">
            + Add contract
        </a>
    </div>

    @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;margin-bottom:1rem;font-size:0.875rem;color:#15803d;">{{ session('success') }}</div>
    @endif

    @if($contracts->isEmpty())
        <div style="text-align:center;padding:64px 24px;color:#94a3b8;">
            <p style="font-size:1rem;font-weight:600;color:#64748b;">No historical contracts yet</p>
            <p style="font-size:0.875rem;margin-top:4px;">Add contracts you know about to track expiry and monitor for replacement tenders.</p>
        </div>
    @else
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:0.8125rem;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                        <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;">Buyer</th>
                        <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;">Category</th>
                        <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;">Incumbent</th>
                        <th style="padding:10px 16px;text-align:right;font-weight:600;color:#64748b;">Value</th>
                        <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;">Expires</th>
                        <th style="padding:10px 16px;text-align:left;font-weight:600;color:#64748b;">Status</th>
                        <th style="padding:10px 16px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($contracts as $hc)
                        @php
                            $alert = $hc->expiry_alert;
                            $alertStyle = match($alert) {
                                'expired'  => 'background:#fef2f2;',
                                'critical' => 'background:#fff7ed;',
                                'warning'  => 'background:#fefce8;',
                                default    => '',
                            };
                        @endphp
                        <tr style="border-bottom:1px solid #f1f5f9;{{ $alertStyle }}" onmouseover="this.style.filter='brightness(0.97)'" onmouseout="this.style.filter=''">
                            <td style="padding:10px 16px;font-weight:600;color:#1e293b;">{{ $hc->buyer }}</td>
                            <td style="padding:10px 16px;color:#475569;">{{ $hc->product_category }}</td>
                            <td style="padding:10px 16px;color:#64748b;">{{ $hc->incumbent_supplier ?? '—' }}</td>
                            <td style="padding:10px 16px;text-align:right;color:#334155;font-weight:600;">{{ $hc->value_display }}</td>
                            <td style="padding:10px 16px;white-space:nowrap;">
                                @if($hc->contract_end)
                                    <span style="color:{{ in_array($alert, ['expired','critical']) ? '#dc2626' : ($alert === 'warning' ? '#d97706' : '#334155') }};font-weight:{{ $alert ? '700' : '400' }};">
                                        {{ $hc->contract_end->format('d M Y') }}
                                    </span>
                                    @if($alert)
                                        <br><span style="font-size:0.7rem;color:{{ in_array($alert, ['expired','critical']) ? '#dc2626' : '#d97706' }};">
                                            {{ $alert === 'expired' ? 'Expired' : $hc->days_until_expiry . ' days' }}
                                        </span>
                                    @endif
                                @else
                                    <span style="color:#94a3b8;">—</span>
                                @endif
                            </td>
                            <td style="padding:10px 16px;">
                                @php
                                    $statusStyle = match($hc->status) {
                                        'monitoring'        => 'background:#eff6ff;color:#1d4ed8;',
                                        'replacement_found' => 'background:#f0fdf4;color:#15803d;',
                                        'closed'            => 'background:#f8fafc;color:#64748b;',
                                        default => '',
                                    };
                                    $statusLabel = match($hc->status) {
                                        'monitoring'        => 'Monitoring',
                                        'replacement_found' => 'Replacement found',
                                        'closed'            => 'Closed',
                                        default => $hc->status,
                                    };
                                @endphp
                                <span style="font-size:0.72rem;font-weight:600;border-radius:9999px;padding:2px 9px;{{ $statusStyle }}">{{ $statusLabel }}</span>
                                @if($hc->relatedTender)
                                    <br><a href="{{ route('tender-radar.show', $hc->relatedTender) }}" style="font-size:0.68rem;color:#e11d48;text-decoration:none;">View tender →</a>
                                @endif
                            </td>
                            <td style="padding:10px 16px;white-space:nowrap;">
                                <a href="{{ route('tender-radar.historical.edit', $hc) }}" style="font-size:0.75rem;color:#64748b;text-decoration:none;margin-right:10px;">Edit</a>
                                <form method="POST" action="{{ route('tender-radar.historical.destroy', $hc) }}" style="display:inline;" onsubmit="return confirm('Remove this contract?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" style="font-size:0.75rem;color:#dc2626;background:none;border:none;cursor:pointer;padding:0;">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($contracts->hasPages())
            <div style="margin-top:1.5rem;display:flex;justify-content:center;gap:6px;">
                @if($contracts->onFirstPage())
                    <span style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#cbd5e1;font-size:0.8125rem;">← Prev</span>
                @else
                    <a href="{{ $contracts->previousPageUrl() }}" style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#475569;font-size:0.8125rem;text-decoration:none;background:#fff;">← Prev</a>
                @endif
                <span style="padding:6px 12px;font-size:0.8125rem;color:#64748b;">Page {{ $contracts->currentPage() }} of {{ $contracts->lastPage() }}</span>
                @if($contracts->hasMorePages())
                    <a href="{{ $contracts->nextPageUrl() }}" style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#475569;font-size:0.8125rem;text-decoration:none;background:#fff;">Next →</a>
                @else
                    <span style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#cbd5e1;font-size:0.8125rem;">Next →</span>
                @endif
            </div>
        @endif
    @endif

</main>
</x-layout>
