<x-layout title="A1 Print Schedule — Lockie Portal">

    <nav class="bg-slate-900 shadow-lg">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" class="h-12 w-auto">
            </a>
            <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white text-sm transition-colors">&#8592; Dashboard</a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        {{-- Page header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:1.5rem;flex-wrap:wrap;">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">A1 Print Schedule</h1>
                <p class="text-slate-500 text-sm mt-1">
                    Manage machine queues, track progress, and plan delivery dates.
                    @php
                        $lastSync = \App\Models\PrintJob::max('synced_at');
                    @endphp
                    @if($lastSync)
                        <span class="text-slate-400">
                            &bull; Last synced:
                            @php
                                $mins = (int) now()->diffInMinutes($lastSync);
                            @endphp
                            @if($mins < 2)
                                just now
                            @elseif($mins < 60)
                                {{ $mins }} mins ago
                            @else
                                {{ \Carbon\Carbon::parse($lastSync)->format('d M Y, H:i') }}
                            @endif
                        </span>
                    @else
                        <span class="text-slate-400">&bull; Never synced</span>
                    @endif
                </p>
            </div>
            @can('admin')
            <a href="{{ route('admin.print-settings.index') }}"
                style="background:#f1f5f9;color:#64748b;font-size:0.75rem;padding:5px 10px;border-radius:6px;border:1px solid #e2e8f0;display:inline-flex;align-items:center;gap:5px;text-decoration:none;white-space:nowrap;"
                title="Print Schedule Settings">
                <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
                Settings
            </a>
            @endcan
            <button id="sync-btn" onclick="triggerSync()"
                style="background:#1e293b;color:#fff;font-size:0.75rem;padding:5px 10px;border-radius:6px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:5px;white-space:nowrap;">
                <svg id="sync-icon" style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
                <span id="sync-label">Sync</span>
            </button>
        </div>

        {{-- Tab bar --}}
        <div class="overflow-x-auto -mx-4 sm:mx-0 mb-6">
            <div class="flex min-w-max px-4 sm:px-0 gap-1 border-b border-slate-200 pb-0">
                @foreach($boards as $key => $label)
                    <button
                        class="tab-btn px-4 py-2.5 text-sm font-medium rounded-t-lg border-b-2 transition-colors whitespace-nowrap -mb-px
                            {{ $loop->first ? 'active-tab border-rose-600 text-rose-600 bg-white' : 'border-transparent text-slate-500 hover:text-slate-700 hover:bg-slate-50' }}"
                        data-board="{{ $key }}"
                        onclick="switchTab('{{ $key }}')">
                        {{ $label }}
                        @if(isset($boardJobs[$key]) && $boardJobs[$key]->count() > 0)
                            <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 text-xs rounded-full
                                {{ $loop->first ? 'bg-rose-100 text-rose-700' : 'bg-slate-100 text-slate-600' }}"
                                id="tab-count-{{ $key }}">{{ $boardJobs[$key]->count() }}</span>
                        @else
                            <span class="ml-1.5 inline-flex items-center justify-center w-5 h-5 text-xs rounded-full bg-slate-100 text-slate-400"
                                id="tab-count-{{ $key }}">0</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Board panes --}}
        @foreach($boards as $boardKey => $boardLabel)
            <div id="pane-{{ $boardKey }}" class="board-pane {{ !$loop->first ? 'hidden' : '' }}">

                {{-- Machine lead time banner --}}
                @if(in_array($boardKey, $machines))
                    @php
                        $machineJobs = $boardJobs[$boardKey];
                        $totalRemaining = $machineJobs->sum(fn($j) => $j->remaining_quantity);
                        $leadTime = $machineLeadTimes[$boardKey];
                    @endphp
                    <div class="mb-4 bg-blue-50 border border-blue-200 rounded-xl px-4 py-3 text-sm" style="display:flex;flex-wrap:wrap;align-items:center;gap:4px 24px;" id="banner-{{ $boardKey }}">
                        <span class="font-medium text-blue-800">
                            <span class="job-count-{{ $boardKey }}">{{ $machineJobs->count() }}</span> job{{ $machineJobs->count() !== 1 ? 's' : '' }}
                        </span>
                        <span class="text-blue-600">
                            <span class="remaining-sum-{{ $boardKey }}">{{ number_format($totalRemaining) }}</span> packs remaining
                        </span>
                        <span class="text-blue-600">
                            ~<span class="lead-time-{{ $boardKey }}">{{ $leadTime }}</span> days lead time
                            <span class="text-blue-400 text-xs">(350 packs/day)</span>
                        </span>
                    </div>
                @endif

                {{-- Sortable job list --}}
                <div id="sortable-{{ $boardKey }}" style="display:flex;flex-direction:column;gap:16px;" data-board="{{ $boardKey }}">

                    @forelse($boardJobs[$boardKey] as $job)
                        @include('print-schedule._job-card', ['job' => $job, 'boards' => $boards])
                    @empty
                        <div class="empty-state-{{ $boardKey }} text-center py-12 text-slate-400">
                            <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                <rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/>
                            </svg>
                            <p class="text-sm font-medium">No jobs on this board</p>
                        </div>
                    @endforelse

                </div>
            </div>
        @endforeach

    </main>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
    (function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // ─── Tab switching ────────────────────────────────────────────────
        function switchTab(boardKey) {
            document.querySelectorAll('.board-pane').forEach(p => p.classList.add('hidden'));
            document.getElementById('pane-' + boardKey).classList.remove('hidden');

            document.querySelectorAll('.tab-btn').forEach(btn => {
                const isActive = btn.dataset.board === boardKey;
                btn.classList.toggle('active-tab', isActive);
                btn.classList.toggle('border-rose-600', isActive);
                btn.classList.toggle('text-rose-600', isActive);
                btn.classList.toggle('bg-white', isActive);
                btn.classList.toggle('border-transparent', !isActive);
                btn.classList.toggle('text-slate-500', !isActive);
                btn.classList.toggle('hover:text-slate-700', !isActive);
                btn.classList.toggle('hover:bg-slate-50', !isActive);

                const badge = btn.querySelector('span');
                if (badge) {
                    badge.classList.toggle('bg-rose-100', isActive);
                    badge.classList.toggle('text-rose-700', isActive);
                    badge.classList.toggle('bg-slate-100', !isActive);
                    badge.classList.toggle('text-slate-600', !isActive);
                }
            });
        }

        window.switchTab = switchTab;

        // ─── Sync ─────────────────────────────────────────────────────────
        window.triggerSync = function (silent) {
            const btn   = document.getElementById('sync-btn');
            const label = document.getElementById('sync-label');
            const icon  = document.getElementById('sync-icon');
            btn.disabled = true;
            if (!silent) label.textContent = 'Syncing…';
            icon.classList.add('animate-spin');

            fetch('{{ route("print.sync") }}', {
                method:  'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .then(r => r.text())
            .then(text => {
                let data;
                try { data = JSON.parse(text); } catch(e) {
                    if (!silent) alert('Sync failed (non-JSON response):\n' + text.substring(0, 500));
                    btn.disabled = false; label.textContent = 'Sync'; icon.classList.remove('animate-spin');
                    return;
                }
                if (data.success) {
                    window.location.reload();
                } else {
                    if (!silent) alert('Sync failed: ' + (data.error || JSON.stringify(data)));
                    btn.disabled = false;
                    label.textContent = 'Sync';
                    icon.classList.remove('animate-spin');
                }
            })
            .catch(e => {
                if (!silent) alert('Sync request failed: ' + e.message);
                btn.disabled = false;
                label.textContent = 'Sync';
                icon.classList.remove('animate-spin');
            });
        };

        // Auto-sync every 60 minutes (silent — reloads page on success)
        setInterval(function () { triggerSync(true); }, 60 * 60 * 1000);


        function saveReorder(boardKey, orderedIds) {
            fetch('{{ route("print.jobs.reorder") }}', {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order: orderedIds }),
            });
        }

        // ─── Move board ───────────────────────────────────────────────────
        window.moveBoard = function (jobId, board) {
            fetch('/print-schedule/jobs/' + jobId + '/board', {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ board: board }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const card = document.querySelector('.job-card[data-job-id="' + jobId + '"]');
                    if (card) {
                        // Move card to new board's sortable list
                        const target = document.getElementById('sortable-' + board);
                        if (target) {
                            // Remove empty state if present
                            const emptyState = target.querySelector('[class*="empty-state"]');
                            if (emptyState) emptyState.remove();
                            target.appendChild(card);
                        }
                        // Update old board empty state if needed
                        const oldBoard = card.dataset.currentBoard;
                        if (oldBoard) {
                            const oldSortable = document.getElementById('sortable-' + oldBoard);
                            if (oldSortable && oldSortable.querySelectorAll('.job-card').length === 0) {
                                oldSortable.innerHTML = '<div class="text-center py-12 text-slate-400"><svg class="w-10 h-10 mx-auto mb-3 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M9 9h6M9 12h6M9 15h4"/></svg><p class="text-sm font-medium">No jobs on this board</p></div>';
                            }
                            updateTabCount(oldBoard);
                        }
                        card.dataset.currentBoard = board;
                        updateTabCount(board);
                        updateMachineBanner(oldBoard);
                        updateMachineBanner(board);
                    }
                }
            });
        };

        function updateTabCount(boardKey) {
            const badge = document.querySelector('[data-board="' + boardKey + '"] span');
            if (!badge) return;
            const count = document.getElementById('sortable-' + boardKey)?.querySelectorAll('.job-card').length || 0;
            badge.textContent = count;
        }

        function updateMachineBanner(boardKey) {
            if (!boardKey) return;
            const machines    = @json($machines);
            const throughputs = @json($throughputs);
            if (!machines.includes(boardKey)) return;
            const banner = document.getElementById('banner-' + boardKey);
            if (!banner) return;
            const cards = document.querySelectorAll('#sortable-' + boardKey + ' .job-card');
            let totalRemaining = 0;
            cards.forEach(c => {
                totalRemaining += parseInt(c.dataset.remaining || '0', 10);
            });
            const tp       = throughputs[boardKey] || 350;
            const leadTime = (totalRemaining / tp).toFixed(1);
            const countEl = banner.querySelector('.job-count-' + boardKey);
            const remEl   = banner.querySelector('.remaining-sum-' + boardKey);
            const ltEl    = banner.querySelector('.lead-time-' + boardKey);
            if (countEl) countEl.textContent = cards.length;
            if (remEl)   remEl.textContent   = totalRemaining.toLocaleString();
            if (ltEl)    ltEl.textContent    = leadTime;
        }

        // ─── Material checked ─────────────────────────────────────────────
        window.toggleMaterial = function (jobId) {
            const btn   = document.getElementById('material-btn-' + jobId);
            const label = document.getElementById('material-label-' + jobId);
            const isChecked = label && label.textContent.trim() === 'Material checked';
            const newVal = !isChecked;

            fetch('/print-schedule/jobs/' + jobId + '/material', {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ checked: newVal }),
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                if (label) label.textContent = newVal ? 'Material checked' : 'Material?';
                if (btn) {
                    btn.style.background   = newVal ? '#16a34a' : '#f8fafc';
                    btn.style.color        = newVal ? '#fff'    : '#64748b';
                    btn.style.borderColor  = newVal ? '#16a34a' : '#e2e8f0';
                }
            });
        };

        // ─── Toggle notes panel ───────────────────────────────────────────
        window.toggleNotes = function (jobId) {
            const panel = document.getElementById('notes-panel-' + jobId);
            if (panel) panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
        };

        // ─── Add note ─────────────────────────────────────────────────────
        window.addNote = function (jobId) {
            const input = document.getElementById('note-input-' + jobId);
            const body  = input ? input.value.trim() : '';
            if (!body) return;

            const btn = document.getElementById('note-add-btn-' + jobId);
            if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }

            fetch('/print-schedule/jobs/' + jobId + '/notes', {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ body: body }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const list = document.getElementById('notes-list-' + jobId);
                    const note = data.note;
                    const el   = document.createElement('div');
                    el.className = 'note-item';
                    el.style.cssText = 'display:flex;gap:8px;font-size:0.875rem;background:#f8fafc;border-radius:0.5rem;padding:0.75rem;';
                    el.id = 'note-' + note.id;
                    el.innerHTML =
                        '<div class="flex-1 min-w-0">' +
                            '<p class="text-slate-700">' + escapeHtml(note.body) + '</p>' +
                            '<p class="text-xs text-slate-400 mt-1">' + escapeHtml(note.user_name) + ' &bull; ' + escapeHtml(note.created_at) + '</p>' +
                        '</div>' +
                        '<button onclick="deleteNote(' + jobId + ',' + note.id + ')" class="text-slate-300 hover:text-red-500 transition-colors text-lg leading-none flex-shrink-0">&times;</button>';
                    if (list) list.appendChild(el);
                    if (input) input.value = '';
                    // Update note count badge
                    const countBadge = document.getElementById('note-count-' + jobId);
                    if (countBadge) {
                        const count = (parseInt(countBadge.textContent, 10) || 0) + 1;
                        countBadge.textContent = count;
                    }
                }
                if (btn) { btn.disabled = false; btn.textContent = 'Add'; }
            })
            .catch(() => {
                if (btn) { btn.disabled = false; btn.textContent = 'Add'; }
            });
        };

        // ─── Delete note ──────────────────────────────────────────────────
        window.deleteNote = function (jobId, noteId) {
            fetch('/print-schedule/jobs/' + jobId + '/notes/' + noteId, {
                method:  'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const el = document.getElementById('note-' + noteId);
                    if (el) el.remove();
                    const countBadge = document.getElementById('note-count-' + jobId);
                    if (countBadge) {
                        const count = Math.max(0, (parseInt(countBadge.textContent, 10) || 0) - 1);
                        countBadge.textContent = count;
                    }
                }
            });
        };

        // ─── Edit date ────────────────────────────────────────────────────
        window.editDate = function (jobId) {
            const display = document.getElementById('date-display-' + jobId);
            const editor  = document.getElementById('date-editor-' + jobId);
            if (display) display.style.display = 'none';
            if (editor)  editor.style.display  = 'inline-flex';
        };

        window.cancelDate = function (jobId) {
            const display = document.getElementById('date-display-' + jobId);
            const editor  = document.getElementById('date-editor-' + jobId);
            if (display) display.style.display = '';
            if (editor)  editor.style.display  = 'none';
        };

        window.saveDate = function (jobId) {
            const input = document.getElementById('date-input-' + jobId);
            const val   = input ? input.value : '';
            if (!val) return;

            const saveBtn = document.getElementById('date-save-btn-' + jobId);
            if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }

            fetch('/print-schedule/jobs/' + jobId + '/date', {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ required_date: val }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const display     = document.getElementById('date-display-' + jobId);
                    const editor      = document.getElementById('date-editor-' + jobId);
                    const dateText    = document.getElementById('date-text-' + jobId);
                    const changedBadge = document.getElementById('date-changed-badge-' + jobId);

                    // Format the date nicely
                    const d      = new Date(val + 'T00:00:00');
                    const fmtOpts = { day: '2-digit', month: 'short', year: 'numeric' };
                    const fmtDate = d.toLocaleDateString('en-GB', fmtOpts);

                    if (dateText) {
                        dateText.textContent = fmtDate;
                        if (data.date_changed) {
                            dateText.classList.add('text-amber-600');
                            dateText.classList.remove('text-slate-600');
                        } else {
                            dateText.classList.remove('text-amber-600');
                            dateText.classList.add('text-slate-600');
                        }
                    }
                    if (changedBadge) {
                        changedBadge.classList.toggle('hidden', !data.date_changed);
                    }

                    if (display) display.style.display = '';
                    if (editor)  editor.style.display  = 'none';
                } else {
                    alert('Save failed: ' + (data.message || data.error || JSON.stringify(data)));
                }
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save'; }
            })
            .catch(e => {
                alert('Save error: ' + e.message);
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save'; }
            });
        };

        // ─── Toggle date change log ───────────────────────────────────────
        window.toggleDateLog = function (jobId) {
            const log = document.getElementById('date-change-log-' + jobId);
            if (log) log.classList.toggle('hidden');
        };

        // ─── Part complete ────────────────────────────────────────────────
        window.editComplete = function (jobId) {
            const display = document.getElementById('complete-display-' + jobId);
            const editor  = document.getElementById('complete-editor-' + jobId);
            if (display) display.style.display = 'none';
            if (editor)  editor.style.display  = 'inline-flex';
        };

        window.cancelComplete = function (jobId) {
            const display = document.getElementById('complete-display-' + jobId);
            const editor  = document.getElementById('complete-editor-' + jobId);
            if (display) display.style.display = '';
            if (editor)  editor.style.display  = 'none';
        };

        window.saveComplete = function (jobId) {
            const input = document.getElementById('complete-input-' + jobId);
            const val   = input ? parseInt(input.value, 10) : 0;

            const saveBtn = document.getElementById('complete-save-btn-' + jobId);
            if (saveBtn) { saveBtn.disabled = true; saveBtn.textContent = 'Saving…'; }

            fetch('/print-schedule/jobs/' + jobId + '/complete', {
                method:  'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept':       'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ quantity_completed: val }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const display      = document.getElementById('complete-display-' + jobId);
                    const editor       = document.getElementById('complete-editor-' + jobId);
                    const remainingEl  = document.getElementById('remaining-qty-' + jobId);
                    const completedEl  = document.getElementById('completed-qty-' + jobId);

                    if (remainingEl) remainingEl.textContent = data.remaining;
                    if (completedEl) completedEl.textContent = val;

                    // Update card's data-remaining for banner recalc
                    const card = document.querySelector('.job-card[data-job-id="' + jobId + '"]');
                    if (card) {
                        card.dataset.remaining = data.remaining;
                        const board = card.dataset.currentBoard;
                        updateMachineBanner(board);
                    }

                    if (display) display.style.display = '';
                    if (editor)  editor.style.display  = 'none';
                } else {
                    alert('Save failed: ' + (data.message || data.error || JSON.stringify(data)));
                }
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save'; }
            })
            .catch(e => {
                alert('Save error: ' + e.message);
                if (saveBtn) { saveBtn.disabled = false; saveBtn.textContent = 'Save'; }
            });
        };

        // ─── SortableJS (initialised last so a CDN failure can't block other functions) ──
        if (typeof Sortable !== 'undefined') {
            document.querySelectorAll('[id^="sortable-"]').forEach(function (el) {
                Sortable.create(el, {
                    handle:    '.drag-handle',
                    animation: 150,
                    onEnd: function (evt) {
                        const boardKey   = el.dataset.board;
                        const cards      = el.querySelectorAll('.job-card');
                        const orderedIds = Array.from(cards).map(c => c.dataset.jobId);
                        saveReorder(boardKey, orderedIds);
                    },
                });
            });
        }

        // ─── Utility ──────────────────────────────────────────────────────
        function escapeHtml(str) {
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
    })();
    </script>
</x-layout>
