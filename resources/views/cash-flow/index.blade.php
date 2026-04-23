<x-layout title="Cash Flow — Lockie Portal">

    <main class="max-w-full px-4 sm:px-6 py-8" style="max-width:1400px;margin:0 auto;">

        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:1.5rem;">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Cash Flow</h1>
                <p class="text-sm text-slate-500 mt-1">Weekly planning. Click any cell to enter or update a figure.</p>
            </div>
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                <button onclick="openCatModal()"
                    style="padding:7px 14px;border:1px solid #e2e8f0;background:#fff;color:#374151;font-size:0.78rem;font-weight:600;border-radius:8px;cursor:pointer;">
                    Manage Categories
                </button>
            </div>
        </div>

        {{-- Controls --}}
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:1.25rem;">

            {{-- Opening balance --}}
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="font-size:0.75rem;color:#64748b;white-space:nowrap;">Opening balance:</span>
                <span id="ob-display"
                    onclick="editOpeningBalance()"
                    style="font-size:0.82rem;font-weight:700;color:#0f172a;cursor:pointer;padding:5px 10px;border:1px solid #e2e8f0;border-radius:7px;background:#fff;"
                    title="Click to edit">
                    £{{ number_format($openingBalance, 2) }}
                </span>
            </div>

            <div style="width:1px;height:20px;background:#e2e8f0;"></div>

            {{-- Horizon selector --}}
            <div style="display:flex;align-items:center;gap:6px;">
                <span style="font-size:0.75rem;color:#64748b;white-space:nowrap;">Weeks:</span>
                <div style="display:flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                    @foreach([4 => '4', 8 => '8', 13 => '13', 26 => '26'] as $val => $label)
                        <a href="{{ route('cash-flow.index', ['horizon' => $val]) }}"
                            style="padding:5px 10px;font-size:0.75rem;font-weight:600;text-decoration:none;{{ $val !== 4 ? 'border-left:1px solid #e2e8f0;' : '' }}{{ $horizon == $val ? 'background:#0f172a;color:#fff;' : 'background:#fff;color:#64748b;' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Weekly Spreadsheet --}}
        @if($categories->isEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm" style="padding:56px 24px;text-align:center;">
                <svg style="width:36px;height:36px;margin:0 auto 12px;color:#cbd5e1;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                <p style="font-size:0.875rem;font-weight:500;color:#64748b;">No categories set up yet</p>
                <p style="font-size:0.8rem;color:#94a3b8;margin-top:4px;">Click <strong>Manage Categories</strong> to add your income and expense lines.</p>
            </div>
        @else

        <div class="bg-white rounded-xl border border-slate-200 shadow-sm" style="overflow:hidden;">
            <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
                <table style="border-collapse:collapse;font-size:0.82rem;min-width:100%;">
                    {{-- Week header --}}
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                            <th style="padding:10px 16px;text-align:left;font-weight:700;color:#374151;min-width:160px;position:sticky;left:0;background:#f8fafc;z-index:2;border-right:2px solid #e2e8f0;white-space:nowrap;">
                                Category
                            </th>
                            @foreach($weeks as $week)
                            <th style="padding:10px 12px;text-align:right;font-weight:600;color:#64748b;min-width:90px;white-space:nowrap;">
                                <div style="font-size:0.72rem;color:#94a3b8;font-weight:500;">{{ $week->format('D') }}</div>
                                {{ $week->format('d M') }}
                            </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>

                        {{-- Opening Balance --}}
                        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                            <td style="padding:8px 16px;font-weight:700;color:#374151;position:sticky;left:0;background:#f8fafc;z-index:1;border-right:2px solid #e2e8f0;">
                                Opening Balance
                            </td>
                            @foreach($weekKeys as $wd)
                            @php $ob = $weeklyCalc[$wd]['opening']; @endphp
                            <td style="padding:8px 12px;text-align:right;font-weight:700;color:{{ $ob >= 0 ? '#16a34a' : '#dc2626' }};">
                                £{{ number_format($ob, 2) }}
                            </td>
                            @endforeach
                        </tr>

                        {{-- INCOME --}}
                        @if($incomeCategories->isNotEmpty())
                        <tr style="background:#f0fdf4;border-bottom:1px solid #bbf7d0;border-top:2px solid #e2e8f0;">
                            <td style="padding:6px 16px;font-size:0.7rem;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:0.08em;position:sticky;left:0;background:#f0fdf4;z-index:1;border-right:2px solid #e2e8f0;">
                                Income
                            </td>
                            <td colspan="{{ count($weeks) }}" style="background:#f0fdf4;"></td>
                        </tr>

                        @foreach($incomeCategories as $cat)
                        <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fafff9'" onmouseout="this.style.background=''">
                            <td style="padding:8px 16px;color:#0f172a;font-weight:500;position:sticky;left:0;background:#fff;z-index:1;border-right:2px solid #e2e8f0;" onmouseover="this.style.background='#fafff9'" onmouseout="this.style.background='#fff'">
                                {{ $cat->name }}
                            </td>
                            @foreach($weekKeys as $wd)
                            @php $entry = $matrix[$cat->id][$wd] ?? null; @endphp
                            <td onclick="openCellModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $wd }}', '{{ $weeks[array_search($wd, $weekKeys)]->format('d M') }}', '{{ $entry?->amount ?? '' }}', '{{ $entry?->status ?? 'forecast' }}')"
                                style="padding:8px 12px;text-align:right;cursor:pointer;white-space:nowrap;{{ $entry ? ($entry->status === 'actual' ? 'color:#16a34a;font-weight:600;' : 'color:#374151;') : 'color:#cbd5e1;' }}"
                                onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background=''">
                                @if($entry && $entry->amount > 0)
                                    {{ $entry->status === 'forecast' ? '' : '' }}£{{ number_format($entry->amount, 2) }}
                                    @if($entry->status === 'forecast')
                                        <span style="font-size:0.62rem;color:#94a3b8;font-weight:400;margin-left:2px;">F</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach

                        {{-- Income Total --}}
                        <tr style="background:#f0fdf4;border-bottom:2px solid #e2e8f0;border-top:1px solid #bbf7d0;">
                            <td style="padding:8px 16px;font-weight:700;color:#15803d;position:sticky;left:0;background:#f0fdf4;z-index:1;border-right:2px solid #e2e8f0;">Total Income</td>
                            @foreach($weekKeys as $wd)
                            <td style="padding:8px 12px;text-align:right;font-weight:700;color:#15803d;">
                                £{{ number_format($weeklyCalc[$wd]['income'], 2) }}
                            </td>
                            @endforeach
                        </tr>
                        @endif

                        {{-- EXPENSES --}}
                        @if($expenseCategories->isNotEmpty())
                        <tr style="background:#fff7f7;border-bottom:1px solid #fecaca;border-top:2px solid #e2e8f0;">
                            <td style="padding:6px 16px;font-size:0.7rem;font-weight:800;color:#b91c1c;text-transform:uppercase;letter-spacing:0.08em;position:sticky;left:0;background:#fff7f7;z-index:1;border-right:2px solid #e2e8f0;">
                                Expenses
                            </td>
                            <td colspan="{{ count($weeks) }}" style="background:#fff7f7;"></td>
                        </tr>

                        @foreach($expenseCategories as $cat)
                        <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fffafa'" onmouseout="this.style.background=''">
                            <td style="padding:8px 16px;color:#0f172a;font-weight:500;position:sticky;left:0;background:#fff;z-index:1;border-right:2px solid #e2e8f0;" onmouseover="this.style.background='#fffafa'" onmouseout="this.style.background='#fff'">
                                {{ $cat->name }}
                            </td>
                            @foreach($weekKeys as $wd)
                            @php $entry = $matrix[$cat->id][$wd] ?? null; @endphp
                            <td onclick="openCellModal({{ $cat->id }}, '{{ addslashes($cat->name) }}', '{{ $wd }}', '{{ $weeks[array_search($wd, $weekKeys)]->format('d M') }}', '{{ $entry?->amount ?? '' }}', '{{ $entry?->status ?? 'forecast' }}')"
                                style="padding:8px 12px;text-align:right;cursor:pointer;white-space:nowrap;{{ $entry ? ($entry->status === 'actual' ? 'color:#dc2626;font-weight:600;' : 'color:#374151;') : 'color:#cbd5e1;' }}"
                                onmouseover="this.style.background='#fff7f7'" onmouseout="this.style.background=''">
                                @if($entry && $entry->amount > 0)
                                    £{{ number_format($entry->amount, 2) }}
                                    @if($entry->status === 'forecast')
                                        <span style="font-size:0.62rem;color:#94a3b8;font-weight:400;margin-left:2px;">F</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            @endforeach
                        </tr>
                        @endforeach

                        {{-- Expenses Total --}}
                        <tr style="background:#fff7f7;border-bottom:2px solid #e2e8f0;border-top:1px solid #fecaca;">
                            <td style="padding:8px 16px;font-weight:700;color:#b91c1c;position:sticky;left:0;background:#fff7f7;z-index:1;border-right:2px solid #e2e8f0;">Total Expenses</td>
                            @foreach($weekKeys as $wd)
                            <td style="padding:8px 12px;text-align:right;font-weight:700;color:#b91c1c;">
                                £{{ number_format($weeklyCalc[$wd]['expenses'], 2) }}
                            </td>
                            @endforeach
                        </tr>
                        @endif

                        {{-- Closing Balance --}}
                        <tr style="background:#f8fafc;border-top:2px solid #334155;">
                            <td style="padding:10px 16px;font-weight:800;color:#0f172a;position:sticky;left:0;background:#f8fafc;z-index:1;border-right:2px solid #e2e8f0;">
                                Closing Balance
                            </td>
                            @foreach($weekKeys as $wd)
                            @php $cb = $weeklyCalc[$wd]['closing']; @endphp
                            <td style="padding:10px 12px;text-align:right;font-weight:800;color:{{ $cb >= 0 ? '#15803d' : '#b91c1c' }};font-size:0.88rem;">
                                £{{ number_format($cb, 2) }}
                            </td>
                            @endforeach
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>

        <p style="margin-top:8px;font-size:0.72rem;color:#94a3b8;">
            <strong style="color:#374151;">F</strong> = Forecast &nbsp;·&nbsp; Figures without F are actual &nbsp;·&nbsp; Click any cell to edit
        </p>

        @endif

    </main>

    {{-- Cell Edit Modal --}}
    <div id="cell-modal" style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;padding:16px;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.4);" onclick="closeCellModal()"></div>
        <div style="position:relative;background:#fff;border-radius:12px;padding:24px;width:100%;max-width:340px;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <p style="font-size:0.72rem;color:#64748b;margin-bottom:2px;" id="cell-week-label"></p>
            <h3 id="cell-cat-name" style="font-size:0.95rem;font-weight:700;color:#0f172a;margin-bottom:16px;"></h3>

            <div id="cell-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:0.78rem;padding:7px 10px;border-radius:7px;margin-bottom:12px;"></div>

            <div style="margin-bottom:12px;">
                <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Amount (£)</label>
                <input type="number" id="cell-amount" min="0" step="0.01" placeholder="0.00"
                    style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:1rem;color:#0f172a;box-sizing:border-box;outline:none;"
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                <p style="font-size:0.72rem;color:#94a3b8;margin-top:4px;">Leave blank or set to 0 to clear this cell.</p>
            </div>

            <div style="margin-bottom:18px;">
                <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Status</label>
                <div style="display:flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;">
                    <button type="button" id="cell-btn-forecast" onclick="setCellStatus('forecast')"
                        style="flex:1;padding:7px;font-size:0.8rem;font-weight:600;border:none;cursor:pointer;">Forecast</button>
                    <button type="button" id="cell-btn-actual" onclick="setCellStatus('actual')"
                        style="flex:1;padding:7px;font-size:0.8rem;font-weight:600;border:none;border-left:1px solid #e2e8f0;cursor:pointer;">Actual</button>
                </div>
                <input type="hidden" id="cell-status" value="forecast">
                <input type="hidden" id="cell-cat-id">
                <input type="hidden" id="cell-week-start">
            </div>

            <div style="display:flex;gap:8px;">
                <button onclick="closeCellModal()"
                    style="flex:1;padding:8px;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.82rem;font-weight:500;border-radius:8px;cursor:pointer;">
                    Cancel
                </button>
                <button onclick="saveCell()" id="cell-save"
                    style="flex:2;padding:8px;background:#0f172a;color:#fff;font-size:0.82rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                    Save
                </button>
            </div>
        </div>
    </div>

    {{-- Opening Balance Modal --}}
    <div id="ob-modal" style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;padding:16px;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.4);" onclick="closeObModal()"></div>
        <div style="position:relative;background:#fff;border-radius:12px;padding:24px;width:100%;max-width:300px;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <h3 style="font-size:0.95rem;font-weight:700;color:#0f172a;margin-bottom:16px;">Opening Balance</h3>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Amount (£)</label>
                <input type="number" id="ob-amount" min="0" step="0.01"
                    value="{{ $openingBalance }}"
                    style="width:100%;padding:9px 12px;border:1px solid #e2e8f0;border-radius:8px;font-size:1rem;color:#0f172a;box-sizing:border-box;outline:none;"
                    onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
            </div>
            <div style="display:flex;gap:8px;">
                <button onclick="closeObModal()"
                    style="flex:1;padding:8px;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.82rem;font-weight:500;border-radius:8px;cursor:pointer;">
                    Cancel
                </button>
                <button onclick="saveOpeningBalance()" id="ob-save"
                    style="flex:2;padding:8px;background:#0f172a;color:#fff;font-size:0.82rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                    Save
                </button>
            </div>
        </div>
    </div>

    {{-- Manage Categories Modal --}}
    <div id="cat-modal" style="display:none;position:fixed;inset:0;z-index:50;align-items:flex-start;justify-content:center;padding:40px 16px;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.4);" onclick="closeCatModal()"></div>
        <div style="position:relative;background:#fff;border-radius:14px;padding:24px;width:100%;max-width:480px;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
                <h2 style="font-size:0.95rem;font-weight:700;color:#0f172a;">Manage Categories</h2>
                <button onclick="closeCatModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;">
                    <svg style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            {{-- Income categories --}}
            <div style="margin-bottom:20px;">
                <p style="font-size:0.72rem;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Income</p>
                <ul id="income-cat-list" style="list-style:none;padding:0;margin:0 0 8px;">
                    @foreach($incomeCategories as $cat)
                    <li id="cat-{{ $cat->id }}" style="display:flex;align-items:center;justify-content:space-between;padding:7px 10px;border:1px solid #e2e8f0;border-radius:7px;margin-bottom:5px;background:#f8fafc;">
                        <span style="font-size:0.85rem;color:#0f172a;font-weight:500;">{{ $cat->name }}</span>
                        <button onclick="deleteCategory({{ $cat->id }}, 'income')"
                            style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:2px 6px;font-size:0.78rem;border-radius:4px;"
                            onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">Remove</button>
                    </li>
                    @endforeach
                </ul>
                <div style="display:flex;gap:6px;">
                    <input type="text" id="new-income-name" placeholder="New income category…"
                        style="flex:1;padding:7px 10px;border:1px solid #e2e8f0;border-radius:7px;font-size:0.82rem;outline:none;"
                        onfocus="this.style.borderColor='#22c55e'" onblur="this.style.borderColor='#e2e8f0'"
                        onkeydown="if(event.key==='Enter'){addCategory('income');}">
                    <button onclick="addCategory('income')"
                        style="padding:7px 14px;background:#16a34a;color:#fff;font-size:0.78rem;font-weight:600;border-radius:7px;border:none;cursor:pointer;white-space:nowrap;">
                        + Add
                    </button>
                </div>
            </div>

            {{-- Expense categories --}}
            <div>
                <p style="font-size:0.72rem;font-weight:800;color:#b91c1c;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:8px;">Expenses</p>
                <ul id="expense-cat-list" style="list-style:none;padding:0;margin:0 0 8px;">
                    @foreach($expenseCategories as $cat)
                    <li id="cat-{{ $cat->id }}" style="display:flex;align-items:center;justify-content:space-between;padding:7px 10px;border:1px solid #e2e8f0;border-radius:7px;margin-bottom:5px;background:#f8fafc;">
                        <span style="font-size:0.85rem;color:#0f172a;font-weight:500;">{{ $cat->name }}</span>
                        <button onclick="deleteCategory({{ $cat->id }}, 'expense')"
                            style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:2px 6px;font-size:0.78rem;border-radius:4px;"
                            onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">Remove</button>
                    </li>
                    @endforeach
                </ul>
                <div style="display:flex;gap:6px;">
                    <input type="text" id="new-expense-name" placeholder="New expense category…"
                        style="flex:1;padding:7px 10px;border:1px solid #e2e8f0;border-radius:7px;font-size:0.82rem;outline:none;"
                        onfocus="this.style.borderColor='#ef4444'" onblur="this.style.borderColor='#e2e8f0'"
                        onkeydown="if(event.key==='Enter'){addCategory('expense');}">
                    <button onclick="addCategory('expense')"
                        style="padding:7px 14px;background:#dc2626;color:#fff;font-size:0.78rem;font-weight:600;border-radius:7px;border:none;cursor:pointer;white-space:nowrap;">
                        + Add
                    </button>
                </div>
            </div>

            <p style="font-size:0.72rem;color:#94a3b8;margin-top:16px;">Removing a category also removes any figures entered for it.</p>
        </div>
    </div>

    <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    // ── Cell editing ──────────────────────────────────────────────
    function openCellModal(catId, catName, weekStart, weekLabel, amount, status) {
        document.getElementById('cell-cat-id').value    = catId;
        document.getElementById('cell-week-start').value = weekStart;
        document.getElementById('cell-cat-name').textContent  = catName;
        document.getElementById('cell-week-label').textContent = 'w/c ' + weekLabel;
        document.getElementById('cell-amount').value = amount || '';
        document.getElementById('cell-error').style.display = 'none';
        setCellStatus(status || 'forecast');
        document.getElementById('cell-modal').style.display = 'flex';
        setTimeout(() => { document.getElementById('cell-amount').focus(); document.getElementById('cell-amount').select(); }, 50);
    }

    function closeCellModal() { document.getElementById('cell-modal').style.display = 'none'; }

    function setCellStatus(val) {
        document.getElementById('cell-status').value = val;
        const fb = document.getElementById('cell-btn-forecast');
        const ab = document.getElementById('cell-btn-actual');
        if (val === 'forecast') {
            fb.style.cssText += 'background:#0f172a;color:#fff;';
            ab.style.cssText += 'background:#f8fafc;color:#64748b;';
        } else {
            ab.style.cssText += 'background:#16a34a;color:#fff;';
            fb.style.cssText += 'background:#f8fafc;color:#64748b;';
        }
    }

    async function saveCell() {
        const btn = document.getElementById('cell-save');
        btn.disabled = true; btn.textContent = 'Saving…';
        try {
            const res = await fetch('{{ route('cash-flow.cell') }}', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body   : JSON.stringify({
                    category_id: document.getElementById('cell-cat-id').value,
                    week_start : document.getElementById('cell-week-start').value,
                    amount     : document.getElementById('cell-amount').value,
                    status     : document.getElementById('cell-status').value,
                }),
            });
            if (res.ok) { window.location.reload(); return; }
            const data = await res.json();
            document.getElementById('cell-error').textContent   = data.message || 'Error saving.';
            document.getElementById('cell-error').style.display = '';
        } catch {
            document.getElementById('cell-error').textContent   = 'Network error.';
            document.getElementById('cell-error').style.display = '';
        }
        btn.disabled = false; btn.textContent = 'Save';
    }

    // ── Opening balance ───────────────────────────────────────────
    function editOpeningBalance() { document.getElementById('ob-modal').style.display = 'flex'; setTimeout(() => { document.getElementById('ob-amount').focus(); document.getElementById('ob-amount').select(); }, 50); }
    function closeObModal()       { document.getElementById('ob-modal').style.display = 'none'; }

    async function saveOpeningBalance() {
        const btn = document.getElementById('ob-save');
        btn.disabled = true; btn.textContent = 'Saving…';
        const res = await fetch('{{ route('cash-flow.opening-balance') }}', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body   : JSON.stringify({ opening_balance: document.getElementById('ob-amount').value }),
        });
        if (res.ok) window.location.reload();
        btn.disabled = false; btn.textContent = 'Save';
    }

    // ── Categories ────────────────────────────────────────────────
    function openCatModal()  { document.getElementById('cat-modal').style.display = 'flex'; }
    function closeCatModal() { document.getElementById('cat-modal').style.display = 'none'; }

    async function addCategory(type) {
        const input = document.getElementById('new-' + type + '-name');
        const name  = input.value.trim();
        if (!name) return;
        const res = await fetch('{{ route('cash-flow.categories.store') }}', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body   : JSON.stringify({ name, type }),
        });
        if (res.ok) {
            const data = await res.json();
            const list = document.getElementById(type + '-cat-list');
            const li   = document.createElement('li');
            li.id = 'cat-' + data.id;
            li.style.cssText = 'display:flex;align-items:center;justify-content:space-between;padding:7px 10px;border:1px solid #e2e8f0;border-radius:7px;margin-bottom:5px;background:#f8fafc;';
            li.innerHTML = `<span style="font-size:0.85rem;color:#0f172a;font-weight:500;">${data.name}</span>
                <button onclick="deleteCategory(${data.id},'${type}')" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:2px 6px;font-size:0.78rem;border-radius:4px;"
                    onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">Remove</button>`;
            list.appendChild(li);
            input.value = '';
        }
    }

    async function deleteCategory(id, type) {
        if (!confirm('Remove this category? Any figures entered for it will also be deleted.')) return;
        const res = await fetch(`/cash-flow/categories/${id}`, {
            method : 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (res.ok) {
            const el = document.getElementById('cat-' + id);
            if (el) el.remove();
            window.location.reload();
        }
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeCellModal(); closeObModal(); closeCatModal(); }
    });

    // Enter key saves cell modal
    document.getElementById('cell-amount').addEventListener('keydown', e => {
        if (e.key === 'Enter') saveCell();
    });
    </script>

</x-layout>
