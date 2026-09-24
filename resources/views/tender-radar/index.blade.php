<x-layout title="Tender Radar — Lockie Portal">
<main class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    {{-- Header --}}
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;margin-bottom:1.5rem;flex-wrap:wrap;">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Tender Radar</h1>
            <p class="text-sm text-slate-500 mt-1">UK procurement opportunities filtered for Lockie products.</p>
        </div>
        <div style="display:flex;gap:8px;flex-wrap:wrap;">
            <a href="{{ route('tender-radar.historical.index') }}"
                style="display:inline-flex;align-items:center;gap:6px;font-size:0.875rem;padding:8px 14px;border-radius:8px;border:1px solid #e2e8f0;background:#f8fafc;color:#475569;text-decoration:none;white-space:nowrap;">
                <svg style="width:14px;height:14px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3m6-3a9 9 0 1 1-18 0 9 9 0 0 1 18 0z"/></svg>
                Contract History
                @if($statsExpiring > 0)
                    <span style="background:#fef2f2;color:#dc2626;border-radius:9999px;padding:1px 7px;font-size:0.7rem;font-weight:700;">{{ $statsExpiring }}</span>
                @endif
            </a>
        </div>
    </div>

    {{-- Stat cards --}}
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:1.5rem;">
        <a href="?tab=new" style="text-decoration:none;flex:1;min-width:120px;">
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 18px;text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:#1e293b;">{{ $statsNew }}</div>
                <div style="font-size:0.72rem;color:#64748b;margin-top:2px;">New opportunities</div>
            </div>
        </a>
        <a href="?tab=high" style="text-decoration:none;flex:1;min-width:120px;">
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:14px 18px;text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:#dc2626;">{{ $statsHigh }}</div>
                <div style="font-size:0.72rem;color:#b91c1c;margin-top:2px;">🔥 High priority</div>
            </div>
        </a>
        <a href="?tab=closing" style="text-decoration:none;flex:1;min-width:120px;">
            <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:14px 18px;text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:#ea580c;">{{ $statsClosing }}</div>
                <div style="font-size:0.72rem;color:#c2410c;margin-top:2px;">Closing in 14 days</div>
            </div>
        </a>
        @if($statsExpiring > 0)
        <a href="{{ route('tender-radar.historical.index') }}" style="text-decoration:none;flex:1;min-width:120px;">
            <div style="background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:14px 18px;text-align:center;">
                <div style="font-size:1.5rem;font-weight:800;color:#d97706;">{{ $statsExpiring }}</div>
                <div style="font-size:0.72rem;color:#b45309;margin-top:2px;">Contracts expiring</div>
            </div>
        </a>
        @endif
    </div>

    {{-- Expiring contracts alert --}}
    @if($expiringContracts->count())
    <div style="background:#fefce8;border:1px solid #fde68a;border-radius:12px;padding:14px 18px;margin-bottom:1.25rem;">
        <div style="font-size:0.8rem;font-weight:700;color:#92400e;margin-bottom:8px;">⚠️ Contracts approaching expiry — monitor for replacement procurement</div>
        @foreach($expiringContracts as $hc)
            @php $days = $hc->days_until_expiry; @endphp
            <div style="display:flex;align-items:center;gap:12px;padding:4px 0;border-top:1px solid #fde68a;font-size:0.8125rem;color:#78350f;">
                <span style="font-weight:600;">{{ $hc->buyer }}</span>
                <span style="color:#92400e;">{{ $hc->product_category }}</span>
                @if($hc->incumbent_supplier)
                    <span style="color:#a16207;">Prev: {{ $hc->incumbent_supplier }}</span>
                @endif
                <span style="margin-left:auto;font-weight:700;color:{{ $days <= 0 ? '#dc2626' : ($days <= 30 ? '#dc2626' : '#d97706') }};">
                    {{ $days <= 0 ? 'EXPIRED' : ($days === 1 ? '1 day left' : "{$days} days") }}
                </span>
                <a href="{{ route('tender-radar.historical.edit', $hc) }}" style="font-size:0.72rem;color:#b45309;text-decoration:none;">Edit</a>
            </div>
        @endforeach
    </div>
    @endif

    {{-- Tabs --}}
    <div style="display:flex;gap:4px;margin-bottom:1.25rem;flex-wrap:wrap;border-bottom:2px solid #e2e8f0;padding-bottom:0;">
        @foreach([
            ['new',      'New', $statsNew],
            ['high',     '🔥 High Priority', $statsHigh],
            ['closing',  '⏰ Closing Soon', $statsClosing],
            ['possible', '🟡 Possible', null],
            ['reviewed', 'Reviewed', null],
            ['ignored',  'Ignored', null],
        ] as [$key, $label, $badge])
            <a href="?tab={{ $key }}"
                style="padding:8px 14px;font-size:0.8125rem;border-radius:8px 8px 0 0;text-decoration:none;font-weight:{{ $tab === $key ? '700' : '400' }};color:{{ $tab === $key ? '#e11d48' : '#64748b' }};border-bottom:{{ $tab === $key ? '2px solid #e11d48' : '2px solid transparent' }};margin-bottom:-2px;white-space:nowrap;">
                {{ $label }}
                @if($badge !== null && $badge > 0)
                    <span style="background:#e11d48;color:#fff;border-radius:9999px;padding:1px 6px;font-size:0.65rem;margin-left:4px;">{{ $badge }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Tender list --}}
    @if($tenders->isEmpty())
        <div style="text-align:center;padding:64px 24px;color:#94a3b8;">
            <svg style="width:40px;height:40px;margin:0 auto 12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <p style="font-size:1rem;font-weight:600;color:#64748b;">No tenders in this category</p>
            <p style="font-size:0.875rem;margin-top:4px;">Run <code>php artisan tender:fetch</code> to populate the radar.</p>
        </div>
    @else
        <div style="display:flex;flex-direction:column;gap:10px;">
            @foreach($tenders as $tender)
                @php
                    $rel    = $tender->ai_relevance ?? 'possible';
                    $colors = \App\Models\Tender::RELEVANCE_COLOURS[$rel] ?? \App\Models\Tender::RELEVANCE_COLOURS['possible'];
                    $label  = \App\Models\Tender::RELEVANCE_LABELS[$rel] ?? $rel;
                @endphp
                <a href="{{ route('tender-radar.show', $tender) }}" style="text-decoration:none;">
                    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:14px 18px;display:flex;gap:14px;align-items:flex-start;transition:box-shadow 0.15s;"
                        onmouseover="this.style.boxShadow='0 2px 8px rgba(0,0,0,0.08)'" onmouseout="this.style.boxShadow=''">

                        {{-- Score circle --}}
                        <div style="flex-shrink:0;width:44px;height:44px;border-radius:50%;background:{{ $colors['bg'] }};border:2px solid {{ $colors['border'] }};display:flex;align-items:center;justify-content:center;flex-direction:column;">
                            <span style="font-size:0.9rem;font-weight:800;color:{{ $colors['text'] }};line-height:1;">{{ $tender->ai_score ?? '?' }}</span>
                            <span style="font-size:0.55rem;color:{{ $colors['text'] }};line-height:1;">/ 100</span>
                        </div>

                        {{-- Main content --}}
                        <div style="flex:1;min-width:0;">
                            <div style="display:flex;align-items:flex-start;gap:8px;flex-wrap:wrap;">
                                <span style="font-size:0.875rem;font-weight:700;color:#1e293b;flex:1;min-width:200px;">{{ $tender->title }}</span>
                                <span style="flex-shrink:0;font-size:0.7rem;font-weight:600;background:{{ $colors['bg'] }};color:{{ $colors['text'] }};border:1px solid {{ $colors['border'] }};border-radius:9999px;padding:2px 9px;white-space:nowrap;">
                                    {{ $label }}
                                </span>
                            </div>

                            <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:6px;font-size:0.78rem;color:#64748b;">
                                @if($tender->buyer_name)
                                    <span>
                                        <svg style="width:11px;height:11px;display:inline;vertical-align:middle;margin-right:3px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                                        {{ $tender->buyer_name }}
                                    </span>
                                @endif
                                <span>
                                    <svg style="width:11px;height:11px;display:inline;vertical-align:middle;margin-right:3px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                                    {{ $tender->value_display }}
                                </span>
                                @if($tender->deadline_at)
                                    <span style="color:{{ $tender->is_closing_soon ? '#dc2626' : '#64748b' }};font-weight:{{ $tender->is_closing_soon ? '700' : '400' }};">
                                        <svg style="width:11px;height:11px;display:inline;vertical-align:middle;margin-right:3px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                                        Closes {{ $tender->deadline_at->format('d M Y') }}
                                        @if($tender->is_closing_soon)
                                            <span style="background:#fef2f2;color:#dc2626;border-radius:4px;padding:1px 5px;font-size:0.65rem;margin-left:3px;">SOON</span>
                                        @endif
                                    </span>
                                @endif
                                <span style="color:#94a3b8;font-size:0.72rem;">{{ \App\Models\Tender::SOURCE_LABELS[$tender->source] ?? $tender->source }}</span>
                            </div>

                            @if($tender->ai_reasoning)
                                <p style="margin-top:6px;font-size:0.78rem;color:#475569;line-height:1.4;">{{ $tender->ai_reasoning }}</p>
                            @endif

                            @if(!empty($tender->ai_products))
                                <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:4px;">
                                    @foreach($tender->ai_products as $product)
                                        <span style="font-size:0.68rem;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:4px;padding:1px 7px;">{{ $product }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Status badge --}}
                        @if($tender->status !== 'new')
                            <div style="flex-shrink:0;">
                                <span style="font-size:0.7rem;font-weight:600;background:#f1f5f9;color:#64748b;border-radius:6px;padding:3px 8px;">{{ ucfirst($tender->status) }}</span>
                            </div>
                        @endif

                    </div>
                </a>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($tenders->hasPages())
            <div style="margin-top:1.5rem;display:flex;justify-content:center;gap:6px;flex-wrap:wrap;">
                @if($tenders->onFirstPage())
                    <span style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#cbd5e1;font-size:0.8125rem;">← Prev</span>
                @else
                    <a href="{{ $tenders->previousPageUrl() }}" style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#475569;font-size:0.8125rem;text-decoration:none;background:#fff;">← Prev</a>
                @endif
                <span style="padding:6px 12px;font-size:0.8125rem;color:#64748b;">Page {{ $tenders->currentPage() }} of {{ $tenders->lastPage() }}</span>
                @if($tenders->hasMorePages())
                    <a href="{{ $tenders->nextPageUrl() }}" style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#475569;font-size:0.8125rem;text-decoration:none;background:#fff;">Next →</a>
                @else
                    <span style="padding:6px 12px;border-radius:6px;border:1px solid #e2e8f0;color:#cbd5e1;font-size:0.8125rem;">Next →</span>
                @endif
            </div>
        @endif
    @endif

</main>
</x-layout>
