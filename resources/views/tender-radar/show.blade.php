<x-layout title="Tender Detail — Lockie Portal">
<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <div style="margin-bottom:1.25rem;">
        <a href="{{ route('tender-radar.index') }}" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">← Tender Radar</a>
    </div>

    @php
        $rel    = $tender->ai_relevance ?? 'possible';
        $colors = \App\Models\Tender::RELEVANCE_COLOURS[$rel] ?? \App\Models\Tender::RELEVANCE_COLOURS['possible'];
        $label  = \App\Models\Tender::RELEVANCE_LABELS[$rel] ?? $rel;
    @endphp

    @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:10px 16px;margin-bottom:1rem;font-size:0.875rem;color:#15803d;">
            {{ session('success') }}
        </div>
    @endif

    {{-- Title block --}}
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;margin-bottom:1.25rem;">
        <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;">
            <div style="flex-shrink:0;width:56px;height:56px;border-radius:50%;background:{{ $colors['bg'] }};border:2px solid {{ $colors['border'] }};display:flex;align-items:center;justify-content:center;flex-direction:column;">
                <span style="font-size:1.1rem;font-weight:800;color:{{ $colors['text'] }};line-height:1;">{{ $tender->ai_score ?? '?' }}</span>
                <span style="font-size:0.6rem;color:{{ $colors['text'] }};line-height:1;">/ 100</span>
            </div>
            <div style="flex:1;min-width:0;">
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:6px;">
                    <h1 style="font-size:1.15rem;font-weight:800;color:#1e293b;margin:0;">{{ $tender->title }}</h1>
                    <span style="font-size:0.75rem;font-weight:600;background:{{ $colors['bg'] }};color:{{ $colors['text'] }};border:1px solid {{ $colors['border'] }};border-radius:9999px;padding:3px 10px;white-space:nowrap;">{{ $label }}</span>
                    @if($tender->is_closing_soon)
                        <span style="font-size:0.72rem;font-weight:700;background:#fef2f2;color:#dc2626;border:1px solid #fecaca;border-radius:9999px;padding:3px 10px;">⏰ Closing soon</span>
                    @endif
                </div>
                @if($tender->ai_reasoning)
                    <p style="font-size:0.875rem;color:#475569;margin:0;line-height:1.5;">{{ $tender->ai_reasoning }}</p>
                @endif
                @if(!empty($tender->ai_products))
                    <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:4px;">
                        @foreach($tender->ai_products as $product)
                            <span style="font-size:0.72rem;background:#f0fdf4;color:#15803d;border:1px solid #bbf7d0;border-radius:4px;padding:2px 8px;">{{ $product }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start;">

        {{-- Left: details --}}
        <div style="display:flex;flex-direction:column;gap:16px;">

            {{-- Key facts --}}
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;">
                <h2 style="font-size:0.8rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 12px;">Key Details</h2>
                <dl style="display:grid;grid-template-columns:130px 1fr;gap:8px 12px;font-size:0.8125rem;">
                    @if($tender->buyer_name)
                        <dt style="color:#94a3b8;font-weight:500;">Buyer</dt>
                        <dd style="color:#334155;font-weight:600;margin:0;">{{ $tender->buyer_name }}
                            @if($tender->buyer_location) <span style="font-weight:400;color:#94a3b8;">· {{ $tender->buyer_location }}</span>@endif
                        </dd>
                    @endif
                    <dt style="color:#94a3b8;font-weight:500;">Value</dt>
                    <dd style="color:#334155;font-weight:600;margin:0;">{{ $tender->value_display }}</dd>
                    @if($tender->deadline_at)
                        <dt style="color:#94a3b8;font-weight:500;">Closing date</dt>
                        <dd style="color:{{ $tender->is_closing_soon ? '#dc2626' : '#334155' }};font-weight:600;margin:0;">
                            {{ $tender->deadline_at->format('d M Y, H:i') }}
                            @if($tender->is_closing_soon)
                                <span style="font-size:0.7rem;background:#fef2f2;color:#dc2626;border-radius:4px;padding:1px 5px;margin-left:4px;">{{ $tender->deadline_at->diffForHumans() }}</span>
                            @endif
                        </dd>
                    @endif
                    @if($tender->published_at)
                        <dt style="color:#94a3b8;font-weight:500;">Published</dt>
                        <dd style="color:#334155;margin:0;">{{ $tender->published_at->format('d M Y') }}</dd>
                    @endif
                    @if($tender->contract_start || $tender->contract_end)
                        <dt style="color:#94a3b8;font-weight:500;">Contract period</dt>
                        <dd style="color:#334155;margin:0;">
                            {{ $tender->contract_start?->format('d M Y') ?? '?' }}
                            → {{ $tender->contract_end?->format('d M Y') ?? '?' }}
                        </dd>
                    @endif
                    @if($tender->incumbent_supplier)
                        <dt style="color:#94a3b8;font-weight:500;">Incumbent</dt>
                        <dd style="color:#334155;font-weight:600;margin:0;">{{ $tender->incumbent_supplier }}</dd>
                    @endif
                    <dt style="color:#94a3b8;font-weight:500;">Source</dt>
                    <dd style="color:#334155;margin:0;">{{ \App\Models\Tender::SOURCE_LABELS[$tender->source] ?? $tender->source }}</dd>
                    @if($tender->source_url)
                        <dt style="color:#94a3b8;font-weight:500;">Original notice</dt>
                        <dd style="margin:0;"><a href="{{ $tender->source_url }}" target="_blank" style="color:#e11d48;font-size:0.8125rem;text-decoration:none;">View on {{ \App\Models\Tender::SOURCE_LABELS[$tender->source] ?? 'source' }} ↗</a></dd>
                    @endif
                    @if(!empty($tender->cpv_codes))
                        <dt style="color:#94a3b8;font-weight:500;">CPV codes</dt>
                        <dd style="color:#64748b;font-family:monospace;font-size:0.75rem;margin:0;">{{ implode(', ', $tender->cpv_codes) }}</dd>
                    @endif
                </dl>
            </div>

            {{-- Description --}}
            @if($tender->description)
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:18px;">
                <h2 style="font-size:0.8rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 10px;">Description</h2>
                <p style="font-size:0.8125rem;color:#334155;line-height:1.6;margin:0;white-space:pre-wrap;">{{ $tender->description }}</p>
            </div>
            @endif

        </div>

        {{-- Right: actions --}}
        <div style="display:flex;flex-direction:column;gap:12px;">

            {{-- Status update --}}
            <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;">
                <h2 style="font-size:0.8rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.05em;margin:0 0 10px;">Status</h2>
                <form method="POST" action="{{ route('tender-radar.status', $tender) }}">
                    @csrf
                    @method('PATCH')
                    <select name="status"
                        style="width:100%;font-size:0.8125rem;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;color:#334155;background:#fff;margin-bottom:8px;">
                        @foreach(['new' => 'New', 'reviewed' => 'Reviewed', 'shortlisted' => 'Shortlisted', 'applied' => 'Applied', 'won' => 'Won', 'lost' => 'Lost', 'ignored' => 'Ignored'] as $val => $lbl)
                            <option value="{{ $val }}" {{ $tender->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                        @endforeach
                    </select>
                    <textarea name="notes" placeholder="Internal notes…" rows="3"
                        style="width:100%;font-size:0.8125rem;border:1px solid #e2e8f0;border-radius:8px;padding:8px 10px;color:#334155;resize:vertical;box-sizing:border-box;margin-bottom:8px;">{{ $tender->notes }}</textarea>
                    <button type="submit"
                        style="width:100%;background:#1e293b;color:#fff;font-size:0.8125rem;padding:8px;border-radius:8px;border:none;cursor:pointer;font-weight:600;">
                        Save
                    </button>
                </form>
            </div>

            {{-- Meta --}}
            <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:14px;font-size:0.75rem;color:#94a3b8;">
                <div>Added: {{ $tender->created_at->format('d M Y') }}</div>
                @if($tender->alerted_at)
                    <div style="margin-top:3px;">Alerted: {{ $tender->alerted_at->format('d M Y') }}</div>
                @endif
            </div>

        </div>
    </div>

</main>
</x-layout>
