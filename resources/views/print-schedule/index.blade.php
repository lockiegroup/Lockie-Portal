<x-layout title="A1 Print Schedule — Lockie Portal">


    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        {{-- Page header --}}
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:1.5rem;flex-wrap:wrap;">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">A1 Print Schedule</h1>
                <p class="text-slate-500 text-sm mt-1">
                    Manage machine queues, track progress, and plan delivery dates.
                    <span class="text-slate-400" id="last-synced-wrap">
                        &bull; <span id="last-synced-text">{{ $lastSync ? 'just now' : 'Never synced' }}</span>
                    </span>
                </p>
            </div>
            <a href="{{ route('print.overview') }}"
                style="background:#f1f5f9;color:#64748b;font-size:0.75rem;padding:5px 10px;border-radius:6px;border:1px solid #e2e8f0;display:inline-flex;align-items:center;gap:5px;text-decoration:none;white-space:nowrap;">
                <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
                </svg>
                Overview
            </a>
            <a href="{{ route('print.archive') }}"
                style="background:#f1f5f9;color:#64748b;font-size:0.75rem;padding:5px 10px;border-radius:6px;border:1px solid #e2e8f0;display:inline-flex;align-items:center;gap:5px;text-decoration:none;white-space:nowrap;">
                <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/>
                </svg>
                Archive
            </a>
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
            <button onclick="openManualModal()"
                style="background:#16a34a;color:#fff;font-size:0.75rem;padding:5px 10px;border-radius:6px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:5px;white-space:nowrap;">
                <svg style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>
                </svg>
                Manual Job
            </button>
            <button id="sync-btn" onclick="triggerSync()"
                style="background:#1e293b;color:#fff;font-size:0.75rem;padding:5px 10px;border-radius:6px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:5px;white-space:nowrap;">
                <svg id="sync-icon" style="width:12px;height:12px;flex-shrink:0;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 4v6h-6"/><path d="M1 20v-6h6"/>
                    <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                </svg>
                <span id="sync-label">Sync</span>
            </button>
        </div>

        {{-- Search bar --}}
        <div style="margin-bottom:1.25rem;position:relative;">
            <svg style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:15px;height:15px;color:#94a3b8;pointer-events:none;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" id="schedule-search"
                placeholder="Search orders, customer refs, print data…"
                oninput="filterJobs(this.value)"
                autocomplete="off"
                style="width:100%;padding:9px 36px 9px 36px;border:1px solid #e2e8f0;border-radius:0.75rem;font-size:0.875rem;color:#1e293b;background:#fff;outline:none;box-sizing:border-box;"
                onfocus="this.style.borderColor='#e11d48';this.style.boxShadow='0 0 0 3px rgba(225,29,72,0.1)'"
                onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='none'">
            <button id="search-clear-btn" onclick="clearSearch()"
                style="display:none;position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;font-size:1.2rem;line-height:1;padding:2px;">&#215;</button>
        </div>

        {{-- Search results info (shown when searching) --}}
        <div id="search-results-info" style="display:none;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.75rem;padding:8px 16px;margin-bottom:1rem;font-size:0.875rem;color:#64748b;"></div>

        {{-- Tab bar --}}
        <style>#tab-bar{scrollbar-width:none;-ms-overflow-style:none;}#tab-bar::-webkit-scrollbar{display:none;}</style>
        <div id="tab-bar" class="overflow-x-auto -mx-4 sm:mx-0 mb-6">
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

                {{-- Board heading shown during search --}}
                <div id="search-board-label-{{ $boardKey }}" style="display:none;font-size:0.7rem;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:0.08em;margin-bottom:0.75rem;">
                    {{ $boardLabel }}
                </div>

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
                            <span class="text-blue-400 text-xs">({{ $throughputs[$boardKey] }} packs/day)</span>
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
        const csrfToken  = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const throughputs = @json($throughputs);
        const machines    = @json($machines);

        // ─── Last synced ticker ───────────────────────────────────────────
        let lastSyncTs = {{ $lastSync ? \Carbon\Carbon::parse($lastSync)->timestamp : 0 }};
        function updateLastSynced() {
            const el = document.getElementById('last-synced-text');
            if (!el || !lastSyncTs) return;
            const mins = Math.floor((Date.now() / 1000 - lastSyncTs) / 60);
            if (mins < 2)       el.textContent = 'Last synced: just now';
            else if (mins < 60) el.textContent = 'Last synced: ' + mins + ' mins ago';
            else {
                const d = new Date(lastSyncTs * 1000);
                el.textContent = 'Last synced: ' + d.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'})
                    + ', ' + d.toLocaleTimeString('en-GB', {hour:'2-digit', minute:'2-digit'});
            }
        }
        updateLastSynced();
        setInterval(updateLastSynced, 60000);

        // ─── Search ───────────────────────────────────────────────────────
        window.filterJobs = function (query) {
            const q          = query.trim().toLowerCase();
            const searching  = q.length > 0;
            const tabBar     = document.getElementById('tab-bar');
            const info       = document.getElementById('search-results-info');
            const clearBtn   = document.getElementById('search-clear-btn');

            if (clearBtn) clearBtn.style.display = searching ? '' : 'none';

            if (!searching) {
                if (tabBar) tabBar.style.display = '';
                if (info)   info.style.display   = 'none';

                // Restore: show only the active tab's pane
                const activeBoard = document.querySelector('.tab-btn.active-tab')?.dataset.board;
                document.querySelectorAll('.board-pane').forEach(function (pane) {
                    const key = pane.id.replace('pane-', '');
                    pane.style.display = '';
                    pane.classList.toggle('hidden', key !== activeBoard);
                    const lbl = document.getElementById('search-board-label-' + key);
                    if (lbl) lbl.style.display = 'none';
                });
                document.querySelectorAll('.job-card').forEach(function (c) { c.style.display = ''; });
                return;
            }

            if (tabBar) tabBar.style.display = 'none';

            let total = 0;
            document.querySelectorAll('.board-pane').forEach(function (pane) {
                pane.classList.remove('hidden');
                const key   = pane.id.replace('pane-', '');
                const cards = pane.querySelectorAll('.job-card');
                let hits    = 0;
                cards.forEach(function (card) {
                    const match = (card.dataset.searchText || '').includes(q);
                    card.style.display = match ? '' : 'none';
                    if (match) hits++;
                });
                const lbl = document.getElementById('search-board-label-' + key);
                if (lbl) lbl.style.display = hits > 0 ? '' : 'none';
                pane.style.display = hits > 0 ? '' : 'none';
                total += hits;
            });

            if (info) {
                info.style.display = '';
                info.textContent   = total > 0
                    ? total + ' result' + (total !== 1 ? 's' : '') + ' across all boards'
                    : 'No results found';
            }
        };

        window.clearSearch = function () {
            const input = document.getElementById('schedule-search');
            if (input) { input.value = ''; filterJobs(''); input.focus(); }
        };

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
                    lastSyncTs = Math.floor(Date.now() / 1000);
                    updateLastSynced();
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

        // Auto-sync every 60 minutes; if overdue on page load, sync sooner
        const SYNC_INTERVAL_MS = 60 * 60 * 1000;
        const msSinceLastSync  = lastSyncTs ? (Date.now() - lastSyncTs * 1000) : SYNC_INTERVAL_MS;
        const msUntilFirstSync = Math.max(5000, SYNC_INTERVAL_MS - msSinceLastSync);
        setTimeout(function () {
            triggerSync(true);
            setInterval(function () { triggerSync(true); }, SYNC_INTERVAL_MS);
        }, msUntilFirstSync);


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
                        recalculateLateFlags(oldBoard);
                        recalculateLateFlags(board);
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

                    // Update card dataset and recalculate late flag immediately
                    const card = document.querySelector('.job-card[data-job-id="' + jobId + '"]');
                    if (card) {
                        card.dataset.requiredDate = val;
                        recalculateLateFlags(card.dataset.currentBoard);
                    }
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
                        recalculateLateFlags(board);
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
                        recalculateLateFlags(boardKey);
                    },
                });
            });
        }

        // ─── Late flag estimation (mirrors PHP estimatedCompletion logic) ─
        // Mon-Thu = 1.0 full day, Fri = 5/8 day (5 effective hours of 8)
        const DAY_WEIGHTS = {1: 1.0, 2: 1.0, 3: 1.0, 4: 1.0, 5: 5/8};
        const MONTHS = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        function estimatedCompletion(packsNeeded, throughput) {
            const date = new Date();
            date.setHours(0, 0, 0, 0);
            date.setDate(date.getDate() + 1); // work starts next working day
            let remaining = packsNeeded;
            for (let i = 0; i < 500; i++) {
                const weight = DAY_WEIGHTS[date.getDay()] || 0;
                if (weight > 0) {
                    remaining -= throughput * weight;
                    if (remaining <= 0) return new Date(date);
                }
                date.setDate(date.getDate() + 1);
            }
            return date;
        }

        function recalculateLateFlags(boardKey) {
            const isMachine = machines.includes(boardKey);
            const cards = document.querySelectorAll('#sortable-' + boardKey + ' .job-card');

            if (!isMachine) {
                cards.forEach(function (card) {
                    const banner = document.getElementById('late-banner-' + card.dataset.jobId);
                    const estOut = document.getElementById('est-out-' + card.dataset.jobId);
                    if (banner) banner.style.display = 'none';
                    if (estOut) estOut.style.display = 'none';
                });
                return;
            }

            const tp = throughputs[boardKey] || 350;
            let cumulative = 0;
            cards.forEach(function (card) {
                cumulative += parseInt(card.dataset.remaining || '0', 10);
                const banner       = document.getElementById('late-banner-' + card.dataset.jobId);
                const lateText     = banner ? banner.querySelector('.late-text') : null;
                const estOut       = document.getElementById('est-out-' + card.dataset.jobId);
                const requiredDate = card.dataset.requiredDate;
                if (!banner) return;
                if (!requiredDate || cumulative === 0) {
                    banner.style.display = 'none';
                    if (estOut) estOut.style.display = 'none';
                    return;
                }
                const estimated = estimatedCompletion(cumulative, tp);
                const required  = new Date(requiredDate + 'T00:00:00');
                const estStr    = estimated.getDate() + ' ' + MONTHS[estimated.getMonth()];

                if (estOut) { estOut.textContent = '→ est del. ' + estStr; estOut.style.display = ''; }

                if (estimated > required) {
                    const daysLate = Math.round((estimated - required) / 86400000);
                    if (lateText) lateText.textContent = 'Estimated late by ' + daysLate + ' day' + (daysLate !== 1 ? 's' : '') + ' — est. ' + estStr;
                    banner.style.display = 'flex';
                } else {
                    banner.style.display = 'none';
                }
            });
        }

        // Run on page load so server-rendered cards match JS state
        machines.forEach(function (m) { recalculateLateFlags(m); });

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

    {{-- Add Manual Job Modal --}}
    <div id="manual-modal" style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;padding:1rem;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.45);" onclick="closeManualModal()"></div>
        <div style="position:relative;background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
            <h2 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 1.25rem;">Add Manual Job</h2>
            <form id="manual-form">
                @csrf

                <div style="display:grid;grid-template-columns:1fr 2fr;gap:0.75rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Product Code <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" name="product_code" placeholder="e.g. A1-BLU380"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Product Description <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="manual-description" name="product_description" required placeholder="e.g. Metal Detectable Cable Ties"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Print Data <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea name="line_comment" rows="3" placeholder="e.g. PRINTED  LALLEMAND&#10;(Print 35mm FROM LOCKING HEAD)&#10;BLOCK  TYPE"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.8rem;font-family:monospace;box-sizing:border-box;outline:none;resize:vertical;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Customer <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" name="customer_name" placeholder="Customer name"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Ref <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" name="customer_ref" placeholder="Customer ref"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-bottom:1.5rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Order No. <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" name="order_number" placeholder="e.g. SO-1234"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Quantity <span style="color:#dc2626;">*</span></label>
                        <input type="number" name="quantity" required min="1" placeholder="0"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Delivery Date <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="date" name="required_date"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Board <span style="color:#dc2626;">*</span></label>
                    <select name="board" required
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;background:#fff;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                        @foreach(App\Models\PrintJob::BOARDS as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div style="display:flex;gap:0.5rem;">
                    <button type="button" onclick="closeManualModal()"
                        style="flex:1;padding:0.5rem;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.875rem;font-weight:500;border-radius:8px;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit" id="manual-submit"
                        style="flex:2;padding:0.5rem;background:#16a34a;color:#fff;font-size:0.875rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                        Add Job
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        window.openManualModal = function () {
            document.getElementById('manual-modal').style.display = 'flex';
            document.getElementById('manual-description').focus();
        };

        window.closeManualModal = function () {
            document.getElementById('manual-modal').style.display = 'none';
            document.getElementById('manual-form').reset();
        };

        document.getElementById('manual-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            const btn = document.getElementById('manual-submit');
            btn.disabled = true; btn.textContent = 'Adding…';
            const fd   = new FormData(this);
            const body = Object.fromEntries(fd.entries());
            const res  = await fetch('{{ route('print.jobs.manual.store') }}', {
                method : 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body   : JSON.stringify(body),
            });
            if (res.ok) {
                window.location.reload();
            } else {
                const err = await res.json().catch(() => ({}));
                btn.disabled = false; btn.textContent = 'Add Job';
                alert('Error ' + res.status + ': ' + (err.message || JSON.stringify(err)));
            }
        });

        window.archiveManualJob = async function (id) {
            if (!confirm('Archive and remove this manual job?')) return;
            const res = await fetch(`/print-schedule/jobs/${id}/manual-archive`, {
                method : 'POST',
                headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            });
            if (res.ok) {
                const card = document.getElementById('job-card-' + id);
                if (card) card.remove();
            }
        };

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { closeManualModal(); closeEditManualModal(); }
        });
    })();
    </script>

    {{-- Edit Manual Job Modal --}}
    <div id="edit-manual-modal" style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;padding:1rem;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.45);" onclick="closeEditManualModal()"></div>
        <div style="position:relative;background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
            <h2 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 1.25rem;">Edit Manual Job</h2>
            <form id="edit-manual-form">

                <div style="display:grid;grid-template-columns:1fr 2fr;gap:0.75rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Product Code <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" id="edit-product-code" name="product_code" placeholder="e.g. A1-BLU380"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Product Description <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="edit-product-description" name="product_description" required placeholder="e.g. Metal Detectable Cable Ties"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Print Data <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea id="edit-line-comment" name="line_comment" rows="3" placeholder="e.g. PRINTED  LALLEMAND&#10;(Print 35mm FROM LOCKING HEAD)&#10;BLOCK  TYPE"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.8rem;font-family:monospace;box-sizing:border-box;outline:none;resize:vertical;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.75rem;margin-bottom:1rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Customer <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" id="edit-customer-name" name="customer_name" placeholder="Customer name"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Ref <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" id="edit-customer-ref" name="customer_ref" placeholder="Customer ref"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-bottom:1.5rem;">
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Order No. <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" id="edit-order-number" name="order_number" placeholder="e.g. SO-1234"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Quantity <span style="color:#dc2626;">*</span></label>
                        <input type="number" id="edit-quantity" name="order_quantity" required min="1" placeholder="0"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                    <div>
                        <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Delivery Date <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="date" id="edit-required-date" name="required_date"
                            style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                            onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    </div>
                </div>

                <div style="display:flex;gap:0.5rem;">
                    <button type="button" onclick="closeEditManualModal()"
                        style="flex:1;padding:0.5rem;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.875rem;font-weight:500;border-radius:8px;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit" id="edit-manual-submit"
                        style="flex:2;padding:0.5rem;background:#1d4ed8;color:#fff;font-size:0.875rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    (function () {
        const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        let editJobId = null;

        window.openEditManualModal = function (id, productCode, productDescription, lineComment, customerName, customerRef, orderNumber, quantity, requiredDate) {
            editJobId = id;
            document.getElementById('edit-product-code').value        = productCode || '';
            document.getElementById('edit-product-description').value = productDescription || '';
            document.getElementById('edit-line-comment').value        = lineComment || '';
            document.getElementById('edit-customer-name').value       = (customerName === 'Manual' ? '' : customerName) || '';
            document.getElementById('edit-customer-ref').value        = customerRef || '';
            document.getElementById('edit-order-number').value        = (orderNumber === 'MANUAL' ? '' : orderNumber) || '';
            document.getElementById('edit-quantity').value            = quantity || '';
            document.getElementById('edit-required-date').value       = requiredDate || '';
            document.getElementById('edit-manual-modal').style.display = 'flex';
            document.getElementById('edit-product-description').focus();
        };

        window.closeEditManualModal = function () {
            document.getElementById('edit-manual-modal').style.display = 'none';
            document.getElementById('edit-manual-form').reset();
            editJobId = null;
        };

        document.getElementById('edit-manual-form').addEventListener('submit', async function (e) {
            e.preventDefault();
            if (!editJobId) return;
            const btn = document.getElementById('edit-manual-submit');
            btn.disabled = true; btn.textContent = 'Saving…';
            const fd   = new FormData(this);
            const body = Object.fromEntries(fd.entries());
            const res  = await fetch(`/print-schedule/jobs/${editJobId}/manual-update`, {
                method : 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body   : JSON.stringify(body),
            });
            if (res.ok) {
                window.location.reload();
            } else {
                const err = await res.json().catch(() => ({}));
                btn.disabled = false; btn.textContent = 'Save Changes';
                alert('Error ' + res.status + ': ' + (err.message || JSON.stringify(err)));
            }
        });
    })();
    </script>

</x-layout>
