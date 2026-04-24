<x-layout title="Company Policies — Lockie Portal">

    <style>
        .policy-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:0.625rem; }
        @media(max-width:600px){ .policy-grid { grid-template-columns:1fr; } }
    </style>

    <main style="max-width:900px;margin:0 auto;padding:2rem 1rem;">

        <div style="margin-bottom:2rem;">
            <h1 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 0.25rem;">Company Policies</h1>
            <p style="font-size:0.875rem;color:#64748b;margin:0;">Click any policy to open or download the PDF.</p>
        </div>

        @if($grouped->isEmpty())
            <div style="text-align:center;padding:4rem 1rem;background:#fff;border-radius:12px;border:1px solid #e2e8f0;">
                <svg style="width:40px;height:40px;margin:0 auto 1rem;color:#cbd5e1;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14 2 14 8 20 8"/>
                </svg>
                <p style="color:#64748b;font-size:0.875rem;font-weight:500;">No policies have been uploaded yet.</p>
            </div>
        @else
            @foreach($grouped as $category => $policies)
            <div style="margin-bottom:2.25rem;">
                <h2 style="font-size:0.7rem;font-weight:800;color:#64748b;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 0.875rem;padding-bottom:0.5rem;border-bottom:1px solid #f1f5f9;">{{ $category }}</h2>
                <div class="policy-grid">
                    @foreach($policies as $policy)
                    <a href="{{ route('policies.download', $policy) }}"
                       target="_blank"
                       style="display:flex;align-items:flex-start;gap:0.875rem;padding:1rem 1.1rem;background:#fff;border:1px solid #e2e8f0;border-radius:10px;text-decoration:none;"
                       onmouseover="this.style.borderColor='#94a3b8';this.style.boxShadow='0 2px 8px rgba(0,0,0,0.06)'"
                       onmouseout="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
                        <div style="flex-shrink:0;width:34px;height:34px;background:#fef2f2;border-radius:8px;display:flex;align-items:center;justify-content:center;margin-top:1px;">
                            <svg style="width:17px;height:17px;color:#dc2626;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                                <line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/>
                                <polyline points="10 9 9 9 8 9"/>
                            </svg>
                        </div>
                        <div style="flex:1;min-width:0;">
                            <p style="font-size:0.875rem;font-weight:600;color:#0f172a;margin:0;line-height:1.3;">{{ $policy->title }}</p>
                            @if($policy->description)
                            <p style="font-size:0.78rem;color:#64748b;margin:0.2rem 0 0;overflow:hidden;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;">{{ $policy->description }}</p>
                            @endif
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-top:0.5rem;">
                                @if($policy->last_reviewed_at)
                                <span style="font-size:0.7rem;color:#94a3b8;">Updated {{ $policy->last_reviewed_at->format('d M Y') }}</span>
                                @else
                                <span></span>
                                @endif
                                <svg style="width:14px;height:14px;color:#cbd5e1;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endforeach
        @endif

    </main>

</x-layout>
