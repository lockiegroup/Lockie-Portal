<x-layout title="Cash Flow — Lockie Portal">

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

        {{-- Header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:1.75rem;">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Cash Flow</h1>
                <p class="text-sm text-slate-500 mt-1">Plan and track income and expenses across your chosen horizon.</p>
            </div>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                {{-- Horizon selector --}}
                <form method="POST" action="{{ route('cash-flow.horizon') }}" style="display:flex;align-items:center;gap:6px;">
                    @csrf
                    <label style="font-size:0.75rem;color:#64748b;white-space:nowrap;">Planning horizon:</label>
                    <select name="horizon" onchange="this.form.submit()"
                        style="padding:6px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.8rem;color:#1e293b;background:#fff;cursor:pointer;outline:none;">
                        @foreach([3 => '3 months', 6 => '6 months', 12 => '12 months', 18 => '18 months', 24 => '24 months'] as $val => $label)
                            <option value="{{ $val }}" {{ $horizon == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
                <button onclick="openModal()" style="background:#0f172a;color:#fff;font-size:0.8rem;font-weight:600;padding:7px 16px;border-radius:8px;border:none;cursor:pointer;white-space:nowrap;">
                    + Add Entry
                </button>
            </div>
        </div>

        {{-- Monthly Summary --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden" style="margin-bottom:1.5rem;">
            <div style="padding:12px 18px;border-bottom:1px solid #f1f5f9;background:#f8fafc;">
                <h2 style="font-size:0.8rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">Monthly Summary</h2>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                    <thead>
                        <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                            <th style="padding:9px 18px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Month</th>
                            <th style="padding:9px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Income</th>
                            <th style="padding:9px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Expenses</th>
                            <th style="padding:9px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($months as $m)
                            <tr style="border-bottom:1px solid #f1f5f9;">
                                <td style="padding:9px 18px;color:#374151;font-weight:500;">{{ $m['label'] }}</td>
                                <td style="padding:9px 18px;text-align:right;color:{{ $m['income'] > 0 ? '#16a34a' : '#94a3b8' }};">
                                    {{ $m['income'] > 0 ? '£' . number_format($m['income'], 2) : '—' }}
                                </td>
                                <td style="padding:9px 18px;text-align:right;color:{{ $m['expense'] > 0 ? '#dc2626' : '#94a3b8' }};">
                                    {{ $m['expense'] > 0 ? '£' . number_format($m['expense'], 2) : '—' }}
                                </td>
                                <td style="padding:9px 18px;text-align:right;font-weight:600;color:{{ $m['net'] >= 0 ? '#16a34a' : '#dc2626' }};">
                                    {{ $m['net'] >= 0 ? '+' : '' }}£{{ number_format($m['net'], 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    @php
                        $totalIn  = collect($months)->sum('income');
                        $totalOut = collect($months)->sum('expense');
                        $totalNet = $totalIn - $totalOut;
                    @endphp
                    <tfoot>
                        <tr style="background:#f8fafc;border-top:2px solid #e2e8f0;">
                            <td style="padding:10px 18px;font-weight:700;color:#0f172a;">Total</td>
                            <td style="padding:10px 18px;text-align:right;font-weight:700;color:#16a34a;">£{{ number_format($totalIn, 2) }}</td>
                            <td style="padding:10px 18px;text-align:right;font-weight:700;color:#dc2626;">£{{ number_format($totalOut, 2) }}</td>
                            <td style="padding:10px 18px;text-align:right;font-weight:700;color:{{ $totalNet >= 0 ? '#16a34a' : '#dc2626' }};">
                                {{ $totalNet >= 0 ? '+' : '' }}£{{ number_format($totalNet, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Entries Table --}}
        @if($entries->isEmpty())
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm" style="padding:56px 24px;text-align:center;">
                <svg style="width:40px;height:40px;margin:0 auto 12px;color:#cbd5e1;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
                </svg>
                <p style="font-size:0.875rem;font-weight:500;color:#64748b;">No entries yet for this period</p>
                <p style="font-size:0.8rem;color:#94a3b8;margin-top:4px;">Click <strong>+ Add Entry</strong> to get started.</p>
            </div>
        @else
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <div style="padding:12px 18px;border-bottom:1px solid #f1f5f9;background:#f8fafc;display:flex;align-items:center;justify-content:space-between;">
                    <h2 style="font-size:0.8rem;font-weight:700;color:#475569;text-transform:uppercase;letter-spacing:0.06em;">
                        All Entries
                    </h2>
                    <span style="font-size:0.75rem;color:#94a3b8;">{{ $entries->count() }} {{ $entries->count() === 1 ? 'entry' : 'entries' }}</span>
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                        <thead>
                            <tr style="background:#f8fafc;border-bottom:1px solid #e2e8f0;">
                                <th style="padding:9px 18px;text-align:left;font-weight:600;color:#64748b;white-space:nowrap;">Date</th>
                                <th style="padding:9px 18px;text-align:left;font-weight:600;color:#64748b;">Description</th>
                                <th style="padding:9px 18px;text-align:left;font-weight:600;color:#64748b;">Category</th>
                                <th style="padding:9px 18px;text-align:center;font-weight:600;color:#64748b;">Type</th>
                                <th style="padding:9px 18px;text-align:right;font-weight:600;color:#64748b;white-space:nowrap;">Amount</th>
                                <th style="padding:9px 18px;text-align:right;font-weight:600;color:#64748b;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($entries as $entry)
                                <tr style="border-bottom:1px solid #f1f5f9;"
                                    onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''"
                                    data-entry="{{ json_encode([
                                        'id'          => $entry->id,
                                        'entry_date'  => $entry->entry_date->format('Y-m-d'),
                                        'description' => $entry->description,
                                        'type'        => $entry->type,
                                        'amount'      => (string) $entry->amount,
                                        'category'    => $entry->category ?? '',
                                        'notes'       => $entry->notes ?? '',
                                    ]) }}">
                                    <td style="padding:10px 18px;color:#64748b;white-space:nowrap;font-size:0.8rem;">
                                        {{ $entry->entry_date->format('d M Y') }}
                                    </td>
                                    <td style="padding:10px 18px;color:#0f172a;font-weight:500;max-width:260px;">
                                        {{ $entry->description }}
                                        @if($entry->notes)
                                            <div style="font-size:0.75rem;color:#94a3b8;margin-top:2px;font-weight:400;">{{ $entry->notes }}</div>
                                        @endif
                                    </td>
                                    <td style="padding:10px 18px;">
                                        @if($entry->category)
                                            <span style="display:inline-block;padding:2px 8px;background:#f1f5f9;color:#475569;border-radius:999px;font-size:0.72rem;font-weight:500;">{{ $entry->category }}</span>
                                        @else
                                            <span style="color:#cbd5e1;font-size:0.8rem;">—</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 18px;text-align:center;">
                                        @if($entry->type === 'income')
                                            <span style="display:inline-block;padding:2px 10px;background:#dcfce7;color:#15803d;border-radius:999px;font-size:0.72rem;font-weight:600;letter-spacing:0.03em;">IN</span>
                                        @else
                                            <span style="display:inline-block;padding:2px 10px;background:#fee2e2;color:#b91c1c;border-radius:999px;font-size:0.72rem;font-weight:600;letter-spacing:0.03em;">OUT</span>
                                        @endif
                                    </td>
                                    <td style="padding:10px 18px;text-align:right;font-weight:600;color:{{ $entry->type === 'income' ? '#16a34a' : '#dc2626' }};white-space:nowrap;">
                                        {{ $entry->type === 'income' ? '+' : '−' }}£{{ number_format($entry->amount, 2) }}
                                    </td>
                                    <td style="padding:10px 18px;text-align:right;white-space:nowrap;">
                                        <button onclick="editEntry(this.closest('tr'))"
                                            style="background:none;border:none;cursor:pointer;color:#64748b;padding:4px 8px;border-radius:6px;font-size:0.78rem;transition:color 0.15s;"
                                            onmouseover="this.style.color='#0f172a'" onmouseout="this.style.color='#64748b'">Edit</button>
                                        <button onclick="deleteEntry({{ $entry->id }})"
                                            style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px 8px;border-radius:6px;font-size:0.78rem;transition:color 0.15s;"
                                            onmouseover="this.style.color='#dc2626'" onmouseout="this.style.color='#94a3b8'">Delete</button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

    </main>

    {{-- Add / Edit Modal --}}
    <div id="cf-modal" style="display:none;position:fixed;inset:0;z-index:50;align-items:flex-start;justify-content:center;padding:40px 16px;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.45);" onclick="closeModal()"></div>
        <div style="position:relative;background:#fff;border-radius:14px;padding:28px 28px 24px;width:100%;max-width:480px;max-height:90vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,0.15);">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                <h2 id="cf-modal-title" style="font-size:1rem;font-weight:700;color:#0f172a;">Add Entry</h2>
                <button onclick="closeModal()" style="background:none;border:none;cursor:pointer;color:#94a3b8;padding:4px;line-height:0;"
                    onmouseover="this.style.color='#475569'" onmouseout="this.style.color='#94a3b8'">
                    <svg style="width:18px;height:18px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <div id="cf-error" style="display:none;background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;font-size:0.8rem;padding:8px 12px;border-radius:8px;margin-bottom:14px;"></div>

            <form id="cf-form" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" id="cf-id">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Date</label>
                        <input type="date" id="cf-date" required
                            style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Type</label>
                        <select id="cf-type"
                            style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;background:#fff;">
                            <option value="income">Income</option>
                            <option value="expense">Expense</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Description</label>
                    <input type="text" id="cf-description" required placeholder="e.g. Customer payment, Payroll, Rent…"
                        style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Amount (£)</label>
                        <input type="number" id="cf-amount" required min="0.01" step="0.01" placeholder="0.00"
                            style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Category <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" id="cf-category" list="cf-categories-list" placeholder="e.g. Wages, Sales…"
                            style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                        <datalist id="cf-categories-list">
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}">
                            @endforeach
                        </datalist>
                    </div>
                </div>

                <div>
                    <label style="display:block;font-size:0.78rem;font-weight:600;color:#374151;margin-bottom:5px;">Notes <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea id="cf-notes" rows="2" placeholder="Any additional detail…"
                        style="width:100%;padding:8px 10px;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;color:#0f172a;box-sizing:border-box;outline:none;resize:vertical;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>

                <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:4px;">
                    <button type="button" onclick="closeModal()"
                        style="padding:8px 18px;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.85rem;font-weight:500;border-radius:8px;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit" id="cf-submit"
                        style="padding:8px 22px;background:#0f172a;color:#fff;font-size:0.85rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                        Save Entry
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function openModal(entry) {
        document.getElementById('cf-id').value          = entry ? entry.id : '';
        document.getElementById('cf-modal-title').textContent = entry ? 'Edit Entry' : 'Add Entry';
        document.getElementById('cf-date').value        = entry ? entry.entry_date : new Date().toISOString().slice(0, 10);
        document.getElementById('cf-type').value        = entry ? entry.type : 'income';
        document.getElementById('cf-description').value = entry ? entry.description : '';
        document.getElementById('cf-amount').value      = entry ? entry.amount : '';
        document.getElementById('cf-category').value    = entry ? (entry.category || '') : '';
        document.getElementById('cf-notes').value       = entry ? (entry.notes || '') : '';
        document.getElementById('cf-error').style.display = 'none';
        document.getElementById('cf-modal').style.display = 'flex';
        setTimeout(() => document.getElementById('cf-description').focus(), 50);
    }

    function editEntry(row) {
        openModal(JSON.parse(row.dataset.entry));
    }

    function closeModal() {
        document.getElementById('cf-modal').style.display = 'none';
    }

    document.getElementById('cf-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const id     = document.getElementById('cf-id').value;
        const url    = id ? '/cash-flow/' + id : '{{ route('cash-flow.store') }}';
        const method = id ? 'PUT' : 'POST';
        const btn    = document.getElementById('cf-submit');
        btn.disabled = true;
        btn.textContent = 'Saving…';

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type'  : 'application/json',
                    'X-CSRF-TOKEN'  : CSRF,
                    'Accept'        : 'application/json',
                },
                body: JSON.stringify({
                    entry_date  : document.getElementById('cf-date').value,
                    type        : document.getElementById('cf-type').value,
                    description : document.getElementById('cf-description').value,
                    amount      : document.getElementById('cf-amount').value,
                    category    : document.getElementById('cf-category').value || null,
                    notes       : document.getElementById('cf-notes').value   || null,
                }),
            });

            if (res.ok) {
                window.location.reload();
            } else {
                const data = await res.json();
                const msg  = data.message || Object.values(data.errors ?? {}).flat().join(' ') || 'An error occurred.';
                document.getElementById('cf-error').textContent    = msg;
                document.getElementById('cf-error').style.display  = '';
                btn.disabled    = false;
                btn.textContent = 'Save Entry';
            }
        } catch {
            document.getElementById('cf-error').textContent   = 'Network error. Please try again.';
            document.getElementById('cf-error').style.display = '';
            btn.disabled    = false;
            btn.textContent = 'Save Entry';
        }
    });

    async function deleteEntry(id) {
        if (!confirm('Delete this entry? This cannot be undone.')) return;
        const res = await fetch('/cash-flow/' + id, {
            method : 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (res.ok) window.location.reload();
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
    });
    </script>

</x-layout>
