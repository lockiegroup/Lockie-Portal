<x-layout title="A/B Testing — Lockie Portal">
<main style="max-width:1200px;margin:0 auto;padding:2rem 1.5rem;">

    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:1rem;margin-bottom:1.75rem;flex-wrap:wrap;">
        <div>
            <h1 style="font-size:1.5rem;font-weight:700;color:#1e293b;margin:0 0 0.25rem;">A/B Testing</h1>
            <p style="color:#64748b;font-size:0.875rem;margin:0;">Track email test results and build rules for each marketing division.</p>
        </div>
        @if($canEdit)
        <div style="display:flex;gap:0.5rem;align-items:center;">
            <button onclick="openAddDivisionModal()"
                style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.45rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:0.8125rem;cursor:pointer;">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Add Division
            </button>
        </div>
        @endif
    </div>

    {{-- Division Tabs --}}
    @if($divisions->isEmpty())
    <div style="background:#fff;border-radius:12px;border:1px solid #e2e8f0;padding:3rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
        No divisions yet. Add one to get started.
    </div>
    @else

    <div style="display:flex;gap:0.25rem;border-bottom:2px solid #e2e8f0;margin-bottom:1.5rem;overflow-x:auto;scrollbar-width:none;">
        @foreach($divisions as $i => $division)
        <button onclick="showTab({{ $division->id }})"
            id="tab-btn-{{ $division->id }}"
            style="padding:0.6rem 1.25rem;font-size:0.875rem;font-weight:600;border:none;background:none;cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-2px;white-space:nowrap;color:#64748b;transition:color 0.15s,border-color 0.15s;"
            class="tab-btn">
            {{ $division->name }}
        </button>
        @endforeach
    </div>

    @foreach($divisions as $division)
    <div id="tab-{{ $division->id }}" class="tab-panel" style="display:none;">

        <div style="display:grid;grid-template-columns:1fr 340px;gap:1.5rem;align-items:start;">

            {{-- Test History --}}
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                    <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Test History</h2>
                    @if($canEdit)
                    <button onclick="openLogTestModal({{ $division->id }}, '{{ addslashes($division->name) }}')"
                        style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.45rem 0.875rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.8125rem;font-weight:600;border:none;cursor:pointer;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Log Campaign
                    </button>
                    @endif
                </div>

                @if($division->tests->isEmpty())
                <div style="background:#fff;border-radius:10px;border:1px solid #e2e8f0;padding:2.5rem;text-align:center;color:#94a3b8;font-size:0.875rem;">
                    No tests logged yet.
                </div>
                @else
                <div id="tests-{{ $division->id }}" style="display:flex;flex-direction:column;gap:0.75rem;">
                    @foreach($division->tests as $test)
                    @php
                        $testJson = json_encode([
                            'id'               => $test->id,
                            'campaign_name'    => $test->campaign_name,
                            'sent_at'          => $test->sent_at->format('d M Y'),
                            'sent_at_input'    => $test->sent_at->format('Y-m-d'),
                            'test_type'        => $test->test_type,
                            'variant_a'        => $test->variant_a,
                            'variant_a_result' => $test->variant_a_result,
                            'variant_a_ctr'    => $test->variant_a_ctr,
                            'variant_b'        => $test->variant_b,
                            'variant_b_result' => $test->variant_b_result,
                            'variant_b_ctr'    => $test->variant_b_ctr,
                            'winner'           => $test->winner,
                            'notes'            => $test->notes,
                            'revenue'          => $test->revenue,
                        ], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);
                    @endphp
                    <div data-test-id="{{ $test->id }}" style="background:#fff;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;margin-bottom:0.75rem;">
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:700;color:#1e293b;font-size:0.9375rem;">{{ $test->campaign_name }}</div>
                                <div style="font-size:0.75rem;color:#94a3b8;margin-top:0.15rem;">
                                    {{ $test->sent_at->format('d M Y') }}
                                    @if($test->test_type) &middot; {{ $test->test_type_label }} @endif
                                    @if($test->user) &middot; {{ $test->user->name }} @endif
                                </div>
                                @if($test->revenue !== null)
                                <div style="margin-top:0.4rem;font-size:0.8125rem;font-weight:600;color:#0f172a;">Revenue: £{{ number_format($test->revenue, 2) }}</div>
                                @endif
                            </div>
                            @if($canEdit)
                            <div style="display:flex;gap:0.125rem;flex-shrink:0;">
                                <button onclick="openEditTestModal({{ $test->id }}, this)"
                                    style="background:none;border:none;cursor:pointer;color:#cbd5e1;padding:4px;border-radius:4px;line-height:0;"
                                    title="Edit"
                                    data-test='{!! $testJson !!}'>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button onclick="deleteTest({{ $test->id }}, this)"
                                    style="background:none;border:none;cursor:pointer;color:#cbd5e1;padding:4px;border-radius:4px;line-height:0;"
                                    title="Delete">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                                </button>
                            </div>
                            @endif
                        </div>

                        @if($test->variant_a)
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                            <div style="padding:0.75rem;border-radius:8px;border:1px solid {{ $test->winner === 'a' ? '#bbf7d0' : '#e2e8f0' }};background:{{ $test->winner === 'a' ? '#f0fdf4' : '#f8fafc' }};">
                                <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:{{ $test->winner === 'a' ? '#16a34a' : '#94a3b8' }};margin-bottom:0.35rem;">
                                    Variant A @if($test->winner === 'a') ✓ Winner @endif
                                </div>
                                <div style="font-size:0.8125rem;color:#334155;">{{ $test->variant_a }}</div>
                                @if($test->variant_a_result !== null || $test->variant_a_ctr !== null)
                                <div style="display:flex;gap:0.75rem;margin-top:0.5rem;flex-wrap:wrap;">
                                    @if($test->variant_a_result !== null)
                                    <div><div style="font-size:0.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Open</div><div style="font-size:0.9375rem;font-weight:700;color:{{ $test->winner === 'a' ? '#16a34a' : '#1e293b' }};">{{ $test->variant_a_result }}%</div></div>
                                    @endif
                                    @if($test->variant_a_ctr !== null)
                                    <div><div style="font-size:0.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">CTR</div><div style="font-size:0.9375rem;font-weight:700;color:{{ $test->winner === 'a' ? '#16a34a' : '#1e293b' }};">{{ $test->variant_a_ctr }}%</div></div>
                                    @endif
                                </div>
                                @endif
                            </div>
                            <div style="padding:0.75rem;border-radius:8px;border:1px solid {{ $test->winner === 'b' ? '#bbf7d0' : '#e2e8f0' }};background:{{ $test->winner === 'b' ? '#f0fdf4' : '#f8fafc' }};">
                                <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:{{ $test->winner === 'b' ? '#16a34a' : '#94a3b8' }};margin-bottom:0.35rem;">
                                    Variant B @if($test->winner === 'b') ✓ Winner @endif
                                </div>
                                <div style="font-size:0.8125rem;color:#334155;">{{ $test->variant_b }}</div>
                                @if($test->variant_b_result !== null || $test->variant_b_ctr !== null)
                                <div style="display:flex;gap:0.75rem;margin-top:0.5rem;flex-wrap:wrap;">
                                    @if($test->variant_b_result !== null)
                                    <div><div style="font-size:0.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Open</div><div style="font-size:0.9375rem;font-weight:700;color:{{ $test->winner === 'b' ? '#16a34a' : '#1e293b' }};">{{ $test->variant_b_result }}%</div></div>
                                    @endif
                                    @if($test->variant_b_ctr !== null)
                                    <div><div style="font-size:0.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">CTR</div><div style="font-size:0.9375rem;font-weight:700;color:{{ $test->winner === 'b' ? '#16a34a' : '#1e293b' }};">{{ $test->variant_b_ctr }}%</div></div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                        @if($test->winner === 'inconclusive')
                        <div style="margin-top:0.6rem;font-size:0.75rem;color:#94a3b8;">Result: Inconclusive</div>
                        @endif
                        @endif

                        @if($test->notes)
                        <div style="margin-top:0.75rem;padding:0.6rem 0.75rem;background:#f8fafc;border-radius:6px;font-size:0.8125rem;color:#475569;border-left:3px solid #e2e8f0;">{{ $test->notes }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Rules --}}
            <div>
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;">
                    <h2 style="font-size:1rem;font-weight:700;color:#1e293b;margin:0;">Rules &amp; Learnings</h2>
                    @if($canEdit)
                    <button onclick="openAddRuleModal({{ $division->id }})"
                        style="display:inline-flex;align-items:center;gap:0.375rem;padding:0.45rem 0.875rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#64748b;font-size:0.8125rem;cursor:pointer;">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                        Add Rule
                    </button>
                    @endif
                </div>

                <div id="rules-{{ $division->id }}" style="background:#fff;border-radius:10px;border:1px solid #e2e8f0;overflow:hidden;">
                    @if($division->rules->isEmpty())
                    <div style="padding:2rem;text-align:center;color:#94a3b8;font-size:0.875rem;" id="rules-empty-{{ $division->id }}">
                        No rules yet. Add learnings from your test results.
                    </div>
                    @else
                    <ol id="rules-list-{{ $division->id }}" style="list-style:none;margin:0;padding:0.5rem;">
                        @foreach($division->rules as $rule)
                        <li data-rule-id="{{ $rule->id }}"
                            style="display:flex;align-items:flex-start;gap:0.5rem;padding:0.625rem 0.5rem;border-radius:6px;cursor:{{ $canEdit ? 'grab' : 'default' }};"
                            class="rule-item">
                            @if($canEdit)
                            <span style="color:#cbd5e1;padding-top:2px;flex-shrink:0;cursor:grab;">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="19" r="1" fill="currentColor"/><circle cx="15" cy="5" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="19" r="1" fill="currentColor"/></svg>
                            </span>
                            @endif
                            <span class="rule-num" style="flex-shrink:0;font-size:0.75rem;font-weight:700;color:#94a3b8;padding-top:2px;min-width:18px;">{{ $loop->iteration }}.</span>
                            <span class="rule-body" style="flex:1;font-size:0.8125rem;color:#334155;line-height:1.55;">{{ $rule->body }}</span>
                            @if($canEdit)
                            <div style="display:flex;gap:0.25rem;flex-shrink:0;">
                                <button onclick="openEditRuleModal({{ $rule->id }}, this)"
                                    style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:3px;border-radius:4px;line-height:0;"
                                    title="Edit">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button onclick="deleteRule({{ $rule->id }}, this)"
                                    style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:3px;border-radius:4px;line-height:0;"
                                    title="Delete">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
                                </button>
                            </div>
                            @endif
                        </li>
                        @endforeach
                    </ol>
                    @endif
                    @if($canEdit && $division->rules->isNotEmpty())
                    <div id="rules-empty-{{ $division->id }}" style="display:none;padding:2rem;text-align:center;color:#94a3b8;font-size:0.875rem;">No rules yet.</div>
                    @endif
                </div>

                @if($canEdit)
                <div style="margin-top:0.75rem;text-align:right;">
                    <button onclick="deleteDivision({{ $division->id }}, '{{ addslashes($division->name) }}')"
                        style="font-size:0.75rem;color:#cbd5e1;background:none;border:none;cursor:pointer;">
                        Delete division
                    </button>
                </div>
                @endif
            </div>

        </div>
    </div>
    @endforeach

    @endif

</main>

{{-- Log Campaign Modal --}}
<div id="log-test-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,0.5);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="padding:1.5rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="margin:0;font-size:1.0625rem;font-weight:700;color:#1e293b;">Log Campaign — <span id="log-modal-division-name"></span></h2>
            <button onclick="closeLogTestModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="log-test-form" style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
            <input type="hidden" id="log-division-id">

            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Campaign Name</label>
                <input id="log-campaign" type="text" placeholder="e.g. Jan Newsletter"
                    style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                <div>
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Date Sent</label>
                    <input id="log-sent-at" type="date"
                        style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                </div>
                <div>
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Revenue Earned <span style="font-weight:400;color:#94a3b8;">(£, optional)</span></label>
                    <input id="log-revenue" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                </div>
            </div>

            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;font-weight:600;color:#374151;cursor:pointer;user-select:none;">
                <input type="checkbox" id="log-ab-toggle" onchange="toggleAbSection('log')" style="width:15px;height:15px;cursor:pointer;">
                Include A/B test details?
            </label>

            <div id="log-ab-section" style="display:none;flex-direction:column;gap:1rem;padding:1rem;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
                <div>
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">What Was Tested</label>
                    <select id="log-test-type" style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;">
                        <option value="">— Select type —</option>
                        @foreach($testTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 80px 80px;gap:0.5rem;align-items:end;">
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Variant A</label>
                        <input id="log-variant-a" type="text" placeholder="e.g. 'Don't miss out!'"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Open %</label>
                        <input id="log-result-a" type="number" min="0" max="100" step="0.01" placeholder="—"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">CTR %</label>
                        <input id="log-ctr-a" type="number" min="0" max="100" step="0.01" placeholder="—"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 80px 80px;gap:0.5rem;align-items:end;">
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Variant B</label>
                        <input id="log-variant-b" type="text" placeholder="e.g. 'New arrivals inside'"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Open %</label>
                        <input id="log-result-b" type="number" min="0" max="100" step="0.01" placeholder="—"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">CTR %</label>
                        <input id="log-ctr-b" type="number" min="0" max="100" step="0.01" placeholder="—"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                </div>

                <div>
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Winner</label>
                    <select id="log-winner" style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;">
                        <option value="">— Not yet known —</option>
                        <option value="a">Variant A</option>
                        <option value="b">Variant B</option>
                        <option value="inconclusive">Inconclusive</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Notes <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <textarea id="log-notes" rows="2" placeholder="Any observations or context..."
                    style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;resize:vertical;"></textarea>
            </div>

            <div id="log-test-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.8125rem;border-radius:8px;padding:0.625rem 0.875rem;"></div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;padding-top:0.5rem;">
                <button type="button" onclick="closeLogTestModal()"
                    style="padding:0.5rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#374151;font-size:0.875rem;cursor:pointer;">
                    Cancel
                </button>
                <button type="button" id="log-test-submit" onclick="submitLogTest()"
                    style="padding:0.5rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.875rem;font-weight:600;border:none;cursor:pointer;">
                    Log Campaign
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Add Rule Modal --}}
<div id="add-rule-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,0.5);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="margin:0;font-size:1rem;font-weight:700;color:#1e293b;">Add Rule</h2>
            <button onclick="closeAddRuleModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="padding:1.5rem;">
            <input type="hidden" id="add-rule-division-id">
            <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Rule / Learning</label>
            <textarea id="add-rule-body" rows="3" placeholder="e.g. Earlier send times (9am) consistently outperform 11am across all product lines"
                style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;resize:vertical;"></textarea>
            <div id="add-rule-error" style="display:none;margin-top:0.5rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.8125rem;border-radius:8px;padding:0.5rem 0.75rem;"></div>
            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1rem;">
                <button onclick="closeAddRuleModal()"
                    style="padding:0.5rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#374151;font-size:0.875rem;cursor:pointer;">
                    Cancel
                </button>
                <button onclick="submitAddRule()"
                    style="padding:0.5rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.875rem;font-weight:600;border:none;cursor:pointer;">
                    Add Rule
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Rule Modal --}}
<div id="edit-rule-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,0.5);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="margin:0;font-size:1rem;font-weight:700;color:#1e293b;">Edit Rule</h2>
            <button onclick="closeEditRuleModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="padding:1.5rem;">
            <input type="hidden" id="edit-rule-id">
            <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Rule / Learning</label>
            <textarea id="edit-rule-body" rows="3"
                style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;resize:vertical;"></textarea>
            <div id="edit-rule-error" style="display:none;margin-top:0.5rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.8125rem;border-radius:8px;padding:0.5rem 0.75rem;"></div>
            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1rem;">
                <button onclick="closeEditRuleModal()"
                    style="padding:0.5rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#374151;font-size:0.875rem;cursor:pointer;">
                    Cancel
                </button>
                <button onclick="submitEditRule()"
                    style="padding:0.5rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.875rem;font-weight:600;border:none;cursor:pointer;">
                    Save
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Add Division Modal --}}
<div id="add-division-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,0.5);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:400px;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="padding:1.25rem 1.5rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="margin:0;font-size:1rem;font-weight:700;color:#1e293b;">Add Division</h2>
            <button onclick="closeAddDivisionModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <div style="padding:1.5rem;">
            <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Division Name</label>
            <input id="add-division-name" type="text" placeholder="e.g. JW Products"
                style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
            <div id="add-division-error" style="display:none;margin-top:0.5rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.8125rem;border-radius:8px;padding:0.5rem 0.75rem;"></div>
            <div style="display:flex;justify-content:flex-end;gap:0.5rem;margin-top:1rem;">
                <button onclick="closeAddDivisionModal()"
                    style="padding:0.5rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#374151;font-size:0.875rem;cursor:pointer;">
                    Cancel
                </button>
                <button onclick="submitAddDivision()"
                    style="padding:0.5rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.875rem;font-weight:600;border:none;cursor:pointer;">
                    Add Division
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Edit Test Modal --}}
<div id="edit-test-modal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(15,23,42,0.5);align-items:center;justify-content:center;padding:1rem;">
    <div style="background:#fff;border-radius:14px;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.2);">
        <div style="padding:1.5rem;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between;">
            <h2 style="margin:0;font-size:1.0625rem;font-weight:700;color:#1e293b;">Edit Test</h2>
            <button onclick="closeEditTestModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
        </div>
        <form id="edit-test-form" style="padding:1.5rem;display:flex;flex-direction:column;gap:1rem;">
            <input type="hidden" id="edit-test-id">

            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Campaign Name</label>
                <input id="edit-campaign" type="text"
                    style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
                <div>
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Date Sent</label>
                    <input id="edit-sent-at" type="date"
                        style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                </div>
                <div>
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Revenue Earned <span style="font-weight:400;color:#94a3b8;">(£, optional)</span></label>
                    <input id="edit-revenue" type="number" min="0" step="0.01" placeholder="0.00"
                        style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                </div>
            </div>

            <label style="display:flex;align-items:center;gap:0.5rem;font-size:0.875rem;font-weight:600;color:#374151;cursor:pointer;user-select:none;">
                <input type="checkbox" id="edit-ab-toggle" onchange="toggleAbSection('edit')" style="width:15px;height:15px;cursor:pointer;">
                Include A/B test details?
            </label>

            <div id="edit-ab-section" style="display:none;flex-direction:column;gap:1rem;padding:1rem;background:#f8fafc;border-radius:10px;border:1px solid #e2e8f0;">
                <div>
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">What Was Tested</label>
                    <select id="edit-test-type" style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;">
                        <option value="">— Select type —</option>
                        @foreach($testTypes as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:1fr 80px 80px;gap:0.5rem;align-items:end;">
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Variant A</label>
                        <input id="edit-variant-a" type="text"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Open %</label>
                        <input id="edit-result-a" type="number" min="0" max="100" step="0.01" placeholder="—"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">CTR %</label>
                        <input id="edit-ctr-a" type="number" min="0" max="100" step="0.01" placeholder="—"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 80px 80px;gap:0.5rem;align-items:end;">
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Variant B</label>
                        <input id="edit-variant-b" type="text"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Open %</label>
                        <input id="edit-result-b" type="number" min="0" max="100" step="0.01" placeholder="—"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">CTR %</label>
                        <input id="edit-ctr-b" type="number" min="0" max="100" step="0.01" placeholder="—"
                            style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;">
                    </div>
                </div>

                <div>
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Winner</label>
                    <select id="edit-winner" style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;background:#fff;">
                        <option value="">— Not yet known —</option>
                        <option value="a">Variant A</option>
                        <option value="b">Variant B</option>
                        <option value="inconclusive">Inconclusive</option>
                    </select>
                </div>
            </div>

            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:0.35rem;">Notes <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <textarea id="edit-notes" rows="2"
                    style="width:100%;box-sizing:border-box;padding:0.5rem 0.75rem;border:1px solid #d1d5db;border-radius:8px;font-size:0.875rem;color:#1e293b;resize:vertical;"></textarea>
            </div>

            <div id="edit-test-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.8125rem;border-radius:8px;padding:0.625rem 0.875rem;"></div>

            <div style="display:flex;justify-content:flex-end;gap:0.5rem;padding-top:0.5rem;">
                <button type="button" onclick="closeEditTestModal()"
                    style="padding:0.5rem 1rem;border-radius:8px;border:1px solid #e2e8f0;background:#fff;color:#374151;font-size:0.875rem;cursor:pointer;">
                    Cancel
                </button>
                <button type="button" id="edit-test-submit" onclick="submitEditTest()"
                    style="padding:0.5rem 1rem;border-radius:8px;background:#0f172a;color:#fff;font-size:0.875rem;font-weight:600;border:none;cursor:pointer;">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

// ── Tabs ──────────────────────────────────────────────────────────────────────
const firstTabId = {{ $divisions->first()?->id ?? 'null' }};
function showTab(id) {
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.tab-btn').forEach(b => {
        b.style.borderBottomColor = 'transparent';
        b.style.color = '#64748b';
    });
    const panel = document.getElementById('tab-' + id);
    const btn   = document.getElementById('tab-btn-' + id);
    if (panel) panel.style.display = 'block';
    if (btn)   { btn.style.borderBottomColor = '#0f172a'; btn.style.color = '#0f172a'; }
    localStorage.setItem('ab_tab', id);
}
(function () {
    const saved = parseInt(localStorage.getItem('ab_tab'));
    const ids = [{{ $divisions->pluck('id')->join(',') }}];
    showTab(ids.includes(saved) ? saved : firstTabId);
})();

// ── Log Campaign Modal ────────────────────────────────────────────────────────
function toggleAbSection(prefix) {
    const checked = document.getElementById(prefix + '-ab-toggle').checked;
    const section = document.getElementById(prefix + '-ab-section');
    section.style.display = checked ? 'flex' : 'none';
}
function openLogTestModal(divisionId, divisionName) {
    document.getElementById('log-division-id').value = divisionId;
    document.getElementById('log-modal-division-name').textContent = divisionName;
    document.getElementById('log-sent-at').value = new Date().toISOString().slice(0,10);
    document.getElementById('log-test-error').style.display = 'none';
    document.getElementById('log-ab-toggle').checked = false;
    document.getElementById('log-ab-section').style.display = 'none';
    document.getElementById('log-test-modal').style.display = 'flex';
}
function closeLogTestModal() {
    document.getElementById('log-test-modal').style.display = 'none';
    document.getElementById('log-test-form').reset();
    document.getElementById('log-ab-section').style.display = 'none';
}
async function submitLogTest() {
    const btn = document.getElementById('log-test-submit');
    const errEl = document.getElementById('log-test-error');
    const hasAb = document.getElementById('log-ab-toggle').checked;
    errEl.style.display = 'none';
    const body = {
        marketing_division_id: document.getElementById('log-division-id').value,
        campaign_name:         document.getElementById('log-campaign').value.trim(),
        sent_at:               document.getElementById('log-sent-at').value,
        revenue:               document.getElementById('log-revenue').value || null,
        notes:                 document.getElementById('log-notes').value.trim() || null,
    };
    if (hasAb) {
        body.test_type        = document.getElementById('log-test-type').value || null;
        body.variant_a        = document.getElementById('log-variant-a').value.trim() || null;
        body.variant_a_result = document.getElementById('log-result-a').value || null;
        body.variant_a_ctr    = document.getElementById('log-ctr-a').value || null;
        body.variant_b        = document.getElementById('log-variant-b').value.trim() || null;
        body.variant_b_result = document.getElementById('log-result-b').value || null;
        body.variant_b_ctr    = document.getElementById('log-ctr-b').value || null;
        body.winner           = document.getElementById('log-winner').value || null;
    }
    btn.disabled = true; btn.textContent = 'Saving…';
    try {
        const res = await fetch('{{ route('ab-testing.tests.store') }}', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (!res.ok) {
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error saving.');
            errEl.textContent = msgs; errEl.style.display = 'block';
            return;
        }
        closeLogTestModal();
        prependTest(body.marketing_division_id, data.test);
    } catch(e) {
        errEl.textContent = 'Network error. Please try again.'; errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = 'Log Campaign';
    }
}

function prependTest(divisionId, test) {
    const container = document.getElementById('tests-' + divisionId);
    if (!container) { location.reload(); return; }
    container.insertAdjacentHTML('afterbegin', buildTestCardHtml(test));
}

async function deleteTest(id, btn) {
    if (!confirm('Delete this test?')) return;
    try {
        const res = await fetch(`/ab-testing/tests/${id}`, {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        });
        if (res.ok) btn.closest('[data-test-id]').remove();
    } catch(e) { alert('Error deleting test.'); }
}

// ── Edit Test Modal ───────────────────────────────────────────────────────────
let _editTestCard = null;
function openEditTestModal(id, btn) {
    const dataRaw = btn.dataset.test || '';
    const data = dataRaw ? JSON.parse(dataRaw) : {};
    _editTestCard = btn.closest('[data-test-id]');
    const hasAb = !!(data.variant_a || data.variant_b || data.test_type);
    document.getElementById('edit-test-id').value   = id;
    document.getElementById('edit-campaign').value  = data.campaign_name || '';
    document.getElementById('edit-sent-at').value   = data.sent_at_input || data.sent_at || '';
    document.getElementById('edit-revenue').value   = data.revenue ?? '';
    document.getElementById('edit-notes').value     = data.notes || '';
    document.getElementById('edit-ab-toggle').checked = hasAb;
    document.getElementById('edit-ab-section').style.display = hasAb ? 'flex' : 'none';
    document.getElementById('edit-test-type').value = data.test_type || '';
    document.getElementById('edit-variant-a').value = data.variant_a || '';
    document.getElementById('edit-result-a').value  = data.variant_a_result ?? '';
    document.getElementById('edit-ctr-a').value     = data.variant_a_ctr ?? '';
    document.getElementById('edit-variant-b').value = data.variant_b || '';
    document.getElementById('edit-result-b').value  = data.variant_b_result ?? '';
    document.getElementById('edit-ctr-b').value     = data.variant_b_ctr ?? '';
    document.getElementById('edit-winner').value    = data.winner || '';
    document.getElementById('edit-test-error').style.display = 'none';
    document.getElementById('edit-test-modal').style.display = 'flex';
}
function closeEditTestModal() {
    document.getElementById('edit-test-modal').style.display = 'none';
}
async function submitEditTest() {
    const btn = document.getElementById('edit-test-submit');
    const errEl = document.getElementById('edit-test-error');
    const id = document.getElementById('edit-test-id').value;
    const hasAb = document.getElementById('edit-ab-toggle').checked;
    errEl.style.display = 'none';
    const body = {
        campaign_name: document.getElementById('edit-campaign').value.trim(),
        sent_at:       document.getElementById('edit-sent-at').value,
        revenue:       document.getElementById('edit-revenue').value || null,
        notes:         document.getElementById('edit-notes').value.trim() || null,
    };
    if (hasAb) {
        body.test_type        = document.getElementById('edit-test-type').value || null;
        body.variant_a        = document.getElementById('edit-variant-a').value.trim() || null;
        body.variant_a_result = document.getElementById('edit-result-a').value || null;
        body.variant_a_ctr    = document.getElementById('edit-ctr-a').value || null;
        body.variant_b        = document.getElementById('edit-variant-b').value.trim() || null;
        body.variant_b_result = document.getElementById('edit-result-b').value || null;
        body.variant_b_ctr    = document.getElementById('edit-ctr-b').value || null;
        body.winner           = document.getElementById('edit-winner').value || null;
    } else {
        body.test_type = null; body.variant_a = null; body.variant_a_result = null;
        body.variant_a_ctr = null; body.variant_b = null; body.variant_b_result = null;
        body.variant_b_ctr = null; body.winner = null;
    }
    btn.disabled = true; btn.textContent = 'Saving…';
    try {
        const res = await fetch(`/ab-testing/tests/${id}`, {
            method: 'PUT',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify(body),
        });
        const data = await res.json();
        if (!res.ok) {
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error saving.');
            errEl.textContent = msgs; errEl.style.display = 'block'; return;
        }
        closeEditTestModal();
        if (_editTestCard) {
            const tmp = document.createElement('div');
            tmp.innerHTML = buildTestCardHtml(data.test);
            _editTestCard.replaceWith(tmp.firstElementChild);
        }
    } catch(e) {
        errEl.textContent = 'Network error. Please try again.'; errEl.style.display = 'block';
    } finally {
        btn.disabled = false; btn.textContent = 'Save Changes';
    }
}

function buildTestCardHtml(test) {
    const winner = test.winner;
    const metaParts = [escHtml(test.sent_at)];
    if (test.test_type) metaParts.push(escHtml(test.test_type));
    if (test.logged_by) metaParts.push(escHtml(test.logged_by));
    const variantHtml = test.variant_a ? `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;">
            <div style="padding:0.75rem;border-radius:8px;border:1px solid ${winner==='a'?'#bbf7d0':'#e2e8f0'};background:${winner==='a'?'#f0fdf4':'#f8fafc'};">
                <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:${winner==='a'?'#16a34a':'#94a3b8'};margin-bottom:0.35rem;">Variant A${winner==='a'?' ✓ Winner':''}</div>
                <div style="font-size:0.8125rem;color:#334155;">${escHtml(test.variant_a)}</div>
                ${(test.variant_a_result!=null||test.variant_a_ctr!=null)?`<div style="display:flex;gap:0.75rem;margin-top:0.5rem;flex-wrap:wrap;">${test.variant_a_result!=null?`<div><div style="font-size:0.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Open</div><div style="font-size:0.9375rem;font-weight:700;color:${winner==='a'?'#16a34a':'#1e293b'};">${test.variant_a_result}%</div></div>`:''} ${test.variant_a_ctr!=null?`<div><div style="font-size:0.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">CTR</div><div style="font-size:0.9375rem;font-weight:700;color:${winner==='a'?'#16a34a':'#1e293b'};">${test.variant_a_ctr}%</div></div>`:''}</div>`:''}
            </div>
            <div style="padding:0.75rem;border-radius:8px;border:1px solid ${winner==='b'?'#bbf7d0':'#e2e8f0'};background:${winner==='b'?'#f0fdf4':'#f8fafc'};">
                <div style="font-size:0.7rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:${winner==='b'?'#16a34a':'#94a3b8'};margin-bottom:0.35rem;">Variant B${winner==='b'?' ✓ Winner':''}</div>
                <div style="font-size:0.8125rem;color:#334155;">${escHtml(test.variant_b)}</div>
                ${(test.variant_b_result!=null||test.variant_b_ctr!=null)?`<div style="display:flex;gap:0.75rem;margin-top:0.5rem;flex-wrap:wrap;">${test.variant_b_result!=null?`<div><div style="font-size:0.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">Open</div><div style="font-size:0.9375rem;font-weight:700;color:${winner==='b'?'#16a34a':'#1e293b'};">${test.variant_b_result}%</div></div>`:''} ${test.variant_b_ctr!=null?`<div><div style="font-size:0.65rem;color:#94a3b8;text-transform:uppercase;letter-spacing:0.06em;">CTR</div><div style="font-size:0.9375rem;font-weight:700;color:${winner==='b'?'#16a34a':'#1e293b'};">${test.variant_b_ctr}%</div></div>`:''}</div>`:''}
            </div>
        </div>
        ${winner==='inconclusive'?'<div style="margin-top:0.6rem;font-size:0.75rem;color:#94a3b8;">Result: Inconclusive</div>':''}` : '';
    return `<div data-test-id="${test.id}" style="background:#fff;border-radius:10px;border:1px solid #e2e8f0;padding:1.25rem;">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.5rem;margin-bottom:${test.variant_a || test.revenue || test.notes ? '0.75rem' : '0'};">
            <div style="flex:1;min-width:0;">
                <div style="font-weight:700;color:#1e293b;font-size:0.9375rem;">${escHtml(test.campaign_name)}</div>
                <div style="font-size:0.75rem;color:#94a3b8;margin-top:0.15rem;">${metaParts.join(' &middot; ')}</div>
                ${test.revenue!=null?`<div style="margin-top:0.4rem;font-size:0.8125rem;font-weight:600;color:#0f172a;">Revenue: £${parseFloat(test.revenue).toLocaleString('en-GB',{minimumFractionDigits:2,maximumFractionDigits:2})}</div>`:''}
            </div>
            <div style="display:flex;gap:0.125rem;flex-shrink:0;">
                <button onclick="openEditTestModal(${test.id}, this)" data-test="${escAttr(JSON.stringify(test))}" style="background:none;border:none;cursor:pointer;color:#cbd5e1;padding:4px;border-radius:4px;line-height:0;" title="Edit">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </button>
                <button onclick="deleteTest(${test.id}, this)" style="background:none;border:none;cursor:pointer;color:#cbd5e1;padding:4px;border-radius:4px;line-height:0;" title="Delete">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4h6v2"/></svg>
                </button>
            </div>
        </div>
        ${variantHtml}
        ${test.notes?`<div style="margin-top:0.75rem;padding:0.6rem 0.75rem;background:#f8fafc;border-radius:6px;font-size:0.8125rem;color:#475569;border-left:3px solid #e2e8f0;">${escHtml(test.notes)}</div>`:''}
    </div>`;
}

// ── Rules ─────────────────────────────────────────────────────────────────────
let _addRuleDivisionId = null;
function openAddRuleModal(divisionId) {
    _addRuleDivisionId = divisionId;
    document.getElementById('add-rule-body').value = '';
    document.getElementById('add-rule-error').style.display = 'none';
    document.getElementById('add-rule-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('add-rule-body').focus(), 50);
}
function closeAddRuleModal() {
    document.getElementById('add-rule-modal').style.display = 'none';
}
async function submitAddRule() {
    const body = document.getElementById('add-rule-body').value.trim();
    const errEl = document.getElementById('add-rule-error');
    errEl.style.display = 'none';
    if (!body) { errEl.textContent = 'Rule text is required.'; errEl.style.display = 'block'; return; }
    try {
        const res = await fetch('{{ route('ab-testing.rules.store') }}', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ marketing_division_id: _addRuleDivisionId, body }),
        });
        const data = await res.json();
        if (!res.ok) {
            errEl.textContent = data.message || 'Error saving.'; errEl.style.display = 'block'; return;
        }
        closeAddRuleModal();
        appendRule(_addRuleDivisionId, data.rule);
    } catch(e) {
        errEl.textContent = 'Network error.'; errEl.style.display = 'block';
    }
}

function appendRule(divisionId, rule) {
    let list = document.getElementById('rules-list-' + divisionId);
    const container = document.getElementById('rules-' + divisionId);
    const emptyEl = document.getElementById('rules-empty-' + divisionId);
    if (!list) {
        // first rule — build the list
        if (emptyEl) emptyEl.style.display = 'none';
        const ol = document.createElement('ol');
        ol.id = 'rules-list-' + divisionId;
        ol.style.cssText = 'list-style:none;margin:0;padding:0.5rem;';
        container.insertBefore(ol, emptyEl || null);
        list = ol;
        initSortable(list, divisionId);
    }
    const num = list.children.length + 1;
    const li = document.createElement('li');
    li.dataset.ruleId = rule.id;
    li.style.cssText = 'display:flex;align-items:flex-start;gap:0.5rem;padding:0.625rem 0.5rem;border-radius:6px;cursor:grab;';
    li.className = 'rule-item';
    li.innerHTML = `<span style="color:#cbd5e1;padding-top:2px;flex-shrink:0;cursor:grab;">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1" fill="currentColor"/><circle cx="9" cy="12" r="1" fill="currentColor"/><circle cx="9" cy="19" r="1" fill="currentColor"/><circle cx="15" cy="5" r="1" fill="currentColor"/><circle cx="15" cy="12" r="1" fill="currentColor"/><circle cx="15" cy="19" r="1" fill="currentColor"/></svg>
    </span>
    <span class="rule-num" style="flex-shrink:0;font-size:0.75rem;font-weight:700;color:#94a3b8;padding-top:2px;min-width:18px;">${num}.</span>
    <span class="rule-body" style="flex:1;font-size:0.8125rem;color:#334155;line-height:1.55;">${escHtml(rule.body)}</span>
    <div style="display:flex;gap:0.25rem;flex-shrink:0;">
        <button onclick="openEditRuleModal(${rule.id}, this)" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:3px;border-radius:4px;line-height:0;" title="Edit">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
        </button>
        <button onclick="deleteRule(${rule.id}, this)" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:3px;border-radius:4px;line-height:0;" title="Delete">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/></svg>
        </button>
    </div>`;
    list.appendChild(li);
    if (emptyEl) emptyEl.style.display = 'none';
}

let _editRuleId = null, _editRuleBodyEl = null;
function openEditRuleModal(ruleId, btn) {
    _editRuleId = ruleId;
    _editRuleBodyEl = btn.closest('li').querySelector('.rule-body');
    document.getElementById('edit-rule-body').value = _editRuleBodyEl.textContent.trim();
    document.getElementById('edit-rule-error').style.display = 'none';
    document.getElementById('edit-rule-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('edit-rule-body').focus(), 50);
}
function closeEditRuleModal() {
    document.getElementById('edit-rule-modal').style.display = 'none';
}
async function submitEditRule() {
    const body = document.getElementById('edit-rule-body').value.trim();
    const errEl = document.getElementById('edit-rule-error');
    errEl.style.display = 'none';
    if (!body) { errEl.textContent = 'Rule text is required.'; errEl.style.display = 'block'; return; }
    try {
        const res = await fetch(`/ab-testing/rules/${_editRuleId}`, {
            method: 'PUT',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ body }),
        });
        const data = await res.json();
        if (!res.ok) { errEl.textContent = data.message || 'Error saving.'; errEl.style.display = 'block'; return; }
        if (_editRuleBodyEl) _editRuleBodyEl.textContent = data.body;
        closeEditRuleModal();
    } catch(e) {
        errEl.textContent = 'Network error.'; errEl.style.display = 'block';
    }
}

async function deleteRule(id, btn) {
    if (!confirm('Delete this rule?')) return;
    try {
        const res = await fetch(`/ab-testing/rules/${id}`, {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        });
        if (res.ok) {
            const li = btn.closest('li');
            const list = li.parentElement;
            li.remove();
            renumberRules(list);
            if (list && list.children.length === 0) {
                const empty = document.getElementById('rules-empty-' + list.id.replace('rules-list-',''));
                if (empty) empty.style.display = 'block';
            }
        }
    } catch(e) { alert('Error deleting rule.'); }
}

function renumberRules(list) {
    if (!list) return;
    list.querySelectorAll('.rule-num').forEach((el, i) => { el.textContent = (i + 1) + '.'; });
}

function initSortable(list, divisionId) {
    if (!window.Sortable || !list) return;
    Sortable.create(list, {
        animation: 150,
        handle: 'li',
        onEnd: function () {
            renumberRules(list);
            const order = Array.from(list.querySelectorAll('li[data-rule-id]')).map(li => li.dataset.ruleId);
            fetch('{{ route('ab-testing.rules.reorder') }}', {
                method: 'POST',
                headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
                body: JSON.stringify({ order }),
            });
        },
    });
}

// Init sortables for existing lists
document.addEventListener('DOMContentLoaded', function () {
    @foreach($divisions as $division)
    @if($division->rules->isNotEmpty())
    initSortable(document.getElementById('rules-list-{{ $division->id }}'), {{ $division->id }});
    @endif
    @endforeach
});

// ── Add Division ──────────────────────────────────────────────────────────────
function openAddDivisionModal() {
    document.getElementById('add-division-name').value = '';
    document.getElementById('add-division-error').style.display = 'none';
    document.getElementById('add-division-modal').style.display = 'flex';
    setTimeout(() => document.getElementById('add-division-name').focus(), 50);
}
function closeAddDivisionModal() {
    document.getElementById('add-division-modal').style.display = 'none';
}
async function submitAddDivision() {
    const name = document.getElementById('add-division-name').value.trim();
    const errEl = document.getElementById('add-division-error');
    errEl.style.display = 'none';
    if (!name) { errEl.textContent = 'Name is required.'; errEl.style.display = 'block'; return; }
    try {
        const res = await fetch('{{ route('ab-testing.divisions.store') }}', {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({ name }),
        });
        const data = await res.json();
        if (!res.ok) {
            const msgs = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message || 'Error.');
            errEl.textContent = msgs; errEl.style.display = 'block'; return;
        }
        closeAddDivisionModal();
        location.reload();
    } catch(e) {
        errEl.textContent = 'Network error.'; errEl.style.display = 'block';
    }
}

async function deleteDivision(id, name) {
    if (!confirm(`Delete division "${name}"? This is only allowed if it has no tests or rules.`)) return;
    try {
        const res = await fetch(`/ab-testing/divisions/${id}`, {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
        });
        const data = await res.json();
        if (!res.ok) { alert(data.message || 'Cannot delete division.'); return; }
        location.reload();
    } catch(e) { alert('Error deleting division.'); }
}

// ── Utility ───────────────────────────────────────────────────────────────────
function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/"/g,'&quot;');
}

// Close modals on backdrop click
['log-test-modal','edit-test-modal','add-rule-modal','edit-rule-modal','add-division-modal'].forEach(function(id) {
    const el = document.getElementById(id);
    if (el) el.addEventListener('click', function(e) { if (e.target === el) el.style.display = 'none'; });
});
</script>
</x-layout>
