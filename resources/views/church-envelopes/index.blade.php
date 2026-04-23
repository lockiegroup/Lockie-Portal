<x-layout title="Church Envelope Generator — Lockie Portal">

    <main class="max-w-2xl mx-auto px-6 py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Church Envelope Generator</h1>
            <p class="text-slate-500 mt-1">Generate a print-ready Excel file for envelope data merge.</p>
        </div>

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Upload previous spreadsheet --}}
        <div style="background:#f8fafc;border:1.5px dashed #94a3b8;border-radius:12px;padding:20px 24px;margin-bottom:24px;">
            <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                <div style="width:36px;height:36px;background:#1e293b;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                </div>
                <div>
                    <h2 style="font-weight:600;color:#1e293b;margin:0;font-size:15px;">Load from previous spreadsheet</h2>
                    <p style="color:#64748b;font-size:12px;margin:2px 0 0 0;">Pre-fills all fields — just update the start date for the new year.</p>
                </div>
            </div>
            <div style="display:flex;flex-wrap:wrap;gap:10px;align-items:center;">
                <label style="flex:1;min-width:0;display:flex;align-items:center;gap:8px;background:white;border:1px solid #cbd5e1;border-radius:8px;padding:8px 12px;cursor:pointer;">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#64748b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18M9 21V9"/></svg>
                    <span id="parse-file-label" style="font-size:13px;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Choose .xlsx file…</span>
                    <input type="file" id="parse-file" accept=".xlsx,.xls" style="display:none;" onchange="document.getElementById('parse-file-label').textContent=this.files[0]?.name||'Choose .xlsx file…'">
                </label>
                <button type="button" id="parse-btn" onclick="loadFromFile()"
                    style="background:#1e293b;color:white;border:none;border-radius:8px;padding:9px 18px;font-size:13px;font-weight:500;cursor:pointer;white-space:nowrap;flex-shrink:0;">
                    Load from file
                </button>
            </div>
            <p id="parse-status" style="font-size:12px;margin:10px 0 0 0;display:none;"></p>
        </div>

        <form action="{{ route('church-envelopes.generate') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Schedule --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="font-semibold text-slate-800">Schedule</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            First Sunday <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date"
                            value="{{ old('start_date') }}" required
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            Number of weeks <span class="text-slate-400 font-normal text-xs">(52 = standard)</span>
                        </label>
                        <input type="number" name="num_weeks" id="num_weeks"
                            value="{{ old('num_weeks', 52) }}" min="1" max="53" required
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                </div>
                <p id="weeks-preview" class="text-xs text-slate-400"></p>
            </div>

            {{-- Job Details --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="font-semibold text-slate-800">Job Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            Church Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="church" value="{{ old('church') }}" required
                            placeholder="e.g. St. Andrew with Holy Trinity"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">
                            Town <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="town" value="{{ old('town') }}" required
                            placeholder="e.g. HALSTEAD"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                </div>
                <div class="grid grid-cols-1 gap-4" style="margin-top:1rem;">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Design image path</label>
                        @if($designs->isNotEmpty())
                            <select id="design-preset"
                                onchange="document.getElementById('design_path').value=this.value"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition mb-2">
                                <option value="">— None (column G blank) —</option>
                                @foreach($designs as $design)
                                    <option value="{{ $design->path }}" {{ old('design_path') === $design->path ? 'selected' : '' }}>{{ $design->name }}</option>
                                @endforeach
                            </select>
                        @endif
                        <input type="text" name="design_path" id="design_path"
                            value="{{ old('design_path') }}"
                            placeholder="Paste or type the full image path, or leave blank for none"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition font-mono text-sm">
                        <p class="text-xs text-slate-400 mt-1">Selecting a saved design above copies its path here. You can also paste a path directly.</p>
                    </div>
                </div>
            </div>

            {{-- Set Numbers --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="font-semibold text-slate-800">Set Numbers</h2>
                <p class="text-xs text-slate-400">
                    Enter numbers, ranges, or a mix — e.g. <code class="bg-slate-100 px-1 rounded">1-50, 75, 100-110</code>.
                    Leave blank if only printing unnumbered copies.
                </p>
                <div>
                    <textarea name="set_numbers" id="set_numbers" rows="3"
                        placeholder="e.g. 1-50&#10;or 1-50, 75, 100-110&#10;or leave blank for unnumbered only"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition resize-none font-mono text-sm">{{ old('set_numbers') }}</textarea>
                    <p id="set-numbers-count" class="text-xs text-slate-400 mt-1"></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Plus unnumbered copies
                        <span class="text-slate-400 font-normal text-xs">— added after numbered sets, E &amp; F columns blank</span>
                    </label>
                    <input type="number" name="none_copies" id="none_copies"
                        value="{{ old('none_copies', 0) }}" min="0"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>
                <div style="border-top:1px solid #f1f5f9;padding-top:1rem;">
                    <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                        <input type="hidden" name="include_setup" value="0">
                        <input type="checkbox" name="include_setup" id="include_setup" value="1"
                            {{ old('include_setup', '1') !== '0' ? 'checked' : '' }}
                            onchange="updateSummary()"
                            style="margin-top:2px;flex-shrink:0;">
                        <span>
                            <span class="text-sm font-medium text-slate-700">Include 2 sets as setup samples</span>
                            <span class="block text-xs text-slate-400 mt-0.5">Printed first, both numbered <strong>0</strong> — machine setup trial, to be binned.</span>
                        </span>
                    </label>
                </div>
            </div>

            {{-- Special Envelopes --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-800">Special Envelopes</h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Choose position per special: before or after the weekly on the same date, or at the back of the entire set.
                            VT1–VT5 are blank; VT6 = title; VT7 = offering text.
                        </p>
                    </div>
                    <button type="button" onclick="addSpecial()"
                        class="text-sm font-medium text-sky-600 hover:text-sky-800 transition-colors flex-shrink-0 ml-4">+ Add</button>
                </div>

                <div id="specials-list" class="space-y-3">
                    @if(old('specials'))
                        @foreach(old('specials') as $i => $s)
                            <div class="special-row rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <div class="grid grid-cols-2 gap-3 mb-2">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">Title (VT6)</label>
                                        <input type="text" name="specials[{{ $i }}][name]"
                                            placeholder="e.g. Easter"
                                            value="{{ $s['name'] ?? '' }}"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">Date <span class="text-red-400">*</span></label>
                                        <input type="date" name="specials[{{ $i }}][date]"
                                            value="{{ $s['date'] ?? '' }}"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">VT7 (e.g. Offering)</label>
                                        <input type="text" name="specials[{{ $i }}][vt7]"
                                            placeholder="Offering or Thanks Giving"
                                            value="{{ $s['vt7'] ?? '' }}"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">Position</label>
                                        <div style="display:flex;align-items:center;gap:8px;">
                                            <select name="specials[{{ $i }}][position]"
                                                class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                                <option value="before" {{ ($s['position'] ?? '') === 'before' ? 'selected' : '' }}>Before same date</option>
                                                <option value="after"  {{ ($s['position'] ?? 'after') === 'after'  ? 'selected' : '' }}>After same date (default)</option>
                                                <option value="back"   {{ ($s['position'] ?? '') === 'back'   ? 'selected' : '' }}>At back of set</option>
                                            </select>
                                            <label style="display:flex;align-items:center;gap:6px;white-space:nowrap;" class="cursor-pointer">
                                                <input type="checkbox" name="specials[{{ $i }}][show_date]" value="1"
                                                    {{ !empty($s['show_date']) ? 'checked' : '' }} class="rounded">
                                                <span class="text-xs text-slate-600">Show date</span>
                                            </label>
                                            <button type="button" onclick="this.closest('.special-row').remove(); updateSummary();"
                                                class="text-slate-400 hover:text-red-500 transition-colors text-xl leading-none flex-shrink-0">&times;</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <p class="text-xs text-slate-400" id="no-specials-msg" style="{{ old('specials') ? 'display:none' : '' }}">
                    No special envelopes added. Click + Add to include Easter, Christmas, etc.
                </p>
            </div>

            {{-- Diocese Lines --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="font-semibold text-slate-800">Diocese Lines</h2>
                <p class="text-xs text-slate-400">Printed on every envelope. Edit if your diocese wording differs.</p>
                <div class="space-y-3">
                    <input type="text" name="diocese_1"
                        value="{{ old('diocese_1', 'REGISTERED CHARITY No. 1127357.') }}"
                        placeholder="Diocese Line 1"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    <input type="text" name="diocese_2"
                        value="{{ old('diocese_2') }}"
                        placeholder="Diocese Line 2 (optional)"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    <input type="text" name="diocese_3"
                        value="{{ old('diocese_3') }}"
                        placeholder="Diocese Line 3 (optional)"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>
            </div>

            {{-- Variable Text --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="font-semibold text-slate-800">Variable Text — Weekly Envelopes</h2>
                <p class="text-xs text-slate-400">Select a verse to auto-fill, or choose Custom to type your own text. Special envelopes use VT6 (title) and VT7 (offering text) only.</p>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Verse</label>
                    <select id="verse-select" onchange="applyVerse(this.value)"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        <option value="custom">— Custom (enter below) —</option>
                        @foreach($verses as $verse)
                            <option value="v{{ $verse->id }}">{{ $verse->label }} — {{ $verse->lines[0] ?? '' }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    @for($v = 1; $v <= 8; $v++)
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">VT{{ $v }}</label>
                            <input type="text" name="vt[{{ $v }}]" id="vt-{{ $v }}"
                                value="{{ old('vt.' . $v) }}"
                                placeholder="VT{{ $v }}"
                                oninput="document.getElementById('verse-select').value='custom'"
                                class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        </div>
                    @endfor
                </div>
            </div>

            {{-- Summary & submit --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <p id="summary" class="text-sm text-slate-500 mb-4"></p>
                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-700 text-white font-semibold py-3 rounded-lg transition-colors">
                    Download Excel
                </button>
            </div>

        </form>
    </main>

    <script>
        let specialIndex = {{ old('specials') ? count(old('specials')) : 0 }};

        // ── Parse set numbers (supports ranges 1-50 and individual numbers) ──
        function parseSetNumbers(raw) {
            const nums = [];
            raw.trim().split(/[\s,;]+/).forEach(part => {
                const range = part.match(/^(\d+)-(\d+)$/);
                if (range) {
                    for (let n = parseInt(range[1]); n <= parseInt(range[2]); n++) nums.push(n);
                } else if (/^\d+$/.test(part)) {
                    nums.push(parseInt(part));
                }
            });
            return [...new Set(nums)];
        }

        // ── Weeks preview ────────────────────────────────────────────────────
        function updateWeeksPreview() {
            const val = document.getElementById('start_date').value;
            const n   = parseInt(document.getElementById('num_weeks').value) || 52;
            if (!val) return;
            const start = new Date(val);
            const end   = new Date(start);
            end.setDate(end.getDate() + (n - 1) * 7);
            const fmt = d => d.toLocaleDateString('en-GB', {day:'numeric', month:'long', year:'numeric'});
            document.getElementById('weeks-preview').textContent =
                n + ' weekly envelopes: ' + fmt(start) + ' to ' + fmt(end) + '.';
            updateSummary();
        }
        document.getElementById('start_date').addEventListener('change', function () {
            if (!document.getElementById('num_weeks').value) document.getElementById('num_weeks').value = 52;
            updateWeeksPreview();
        });
        document.getElementById('num_weeks').addEventListener('input', updateWeeksPreview);

        // ── Set numbers counter ──────────────────────────────────────────────
        document.getElementById('set_numbers').addEventListener('input', function () {
            const nums = parseSetNumbers(this.value);
            const el   = document.getElementById('set-numbers-count');
            el.textContent = nums.length ? nums.length + ' numbered set' + (nums.length === 1 ? '' : 's') + ' detected.' : '';
            updateSummary();
        });
        document.getElementById('none_copies').addEventListener('input', updateSummary);

        // ── Add special envelope ─────────────────────────────────────────────
        function addSpecial() {
            document.getElementById('no-specials-msg').style.display = 'none';
            const i   = specialIndex++;
            const row = document.createElement('div');
            row.className = 'special-row rounded-lg border border-slate-200 bg-slate-50 p-3';
            row.innerHTML = `
                <div class="grid grid-cols-2 gap-3 mb-2">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Title (VT6)</label>
                        <input type="text" name="specials[${i}][name]" placeholder="e.g. Easter"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Date <span class="text-red-400">*</span></label>
                        <input type="date" name="specials[${i}][date]"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">VT7 (e.g. Offering)</label>
                        <input type="text" name="specials[${i}][vt7]" placeholder="Offering or Thanks Giving"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">Position</label>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <select name="specials[${i}][position]"
                                class="flex-1 px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                <option value="before">Before same date</option>
                                <option value="after" selected>After same date (default)</option>
                                <option value="back">At back of set</option>
                            </select>
                            <label style="display:flex;align-items:center;gap:6px;white-space:nowrap;" class="cursor-pointer">
                                <input type="checkbox" name="specials[${i}][show_date]" value="1" checked class="rounded">
                                <span class="text-xs text-slate-600">Show date</span>
                            </label>
                            <button type="button" onclick="this.closest('.special-row').remove(); updateSummary();"
                                class="text-slate-400 hover:text-red-500 transition-colors text-xl leading-none flex-shrink-0">&times;</button>
                        </div>
                    </div>
                </div>`;
            document.getElementById('specials-list').appendChild(row);
            updateSummary();
        }

        // ── Summary ──────────────────────────────────────────────────────────
        function updateSummary() {
            const weeks        = parseInt(document.getElementById('num_weeks').value) || 0;
            const specials     = document.querySelectorAll('.special-row').length;
            const numbered     = parseSetNumbers(document.getElementById('set_numbers').value).length;
            const unnumbered   = Math.max(0, parseInt(document.getElementById('none_copies').value) || 0);
            const setupChecked = document.getElementById('include_setup')?.checked ? 1 : 0;
            const totalSets    = numbered + unnumbered;
            const envsPerSet   = weeks + specials;
            const mainPairs    = Math.ceil(totalSets / 2);
            const totalPairs   = mainPairs + setupChecked;
            const totalRows    = totalPairs * envsPerSet;
            const el           = document.getElementById('summary');
            if ((totalSets || setupChecked) && weeks) {
                const parts = [];
                if (setupChecked) parts.push('2 setup samples');
                if (numbered)     parts.push(numbered + ' numbered');
                if (unnumbered)   parts.push(unnumbered + ' unnumbered');
                el.textContent = `${parts.join(' + ')} sets × ${envsPerSet} envelopes = ${totalRows.toLocaleString()} rows (${totalPairs} pairs, 2-up).`;
            } else {
                el.textContent = '';
            }
        }

        updateSummary();

        // ── Verse library ────────────────────────────────────────────────────
        const VERSES = {!! $verses->mapWithKeys(fn($v) => ['v'.$v->id => $v->lines])->toJson() !!};

        function applyVerse(key) {
            if (key === 'custom') return;
            const lines = VERSES[key];
            if (!lines) return;
            for (let i = 1; i <= 8; i++) {
                const el = document.getElementById('vt-' + i);
                if (el) el.value = lines[i - 1] ?? '';
            }
        }

        // ── Load from spreadsheet ────────────────────────────────────────────
        function loadFromFile() {
            const fileInput = document.getElementById('parse-file');
            const statusEl  = document.getElementById('parse-status');
            const btn       = document.getElementById('parse-btn');

            if (!fileInput.files[0]) {
                statusEl.textContent = 'Please select a file first.';
                statusEl.style.display = '';
                statusEl.style.color = '#dc2626';
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Loading…';
            statusEl.textContent = 'Parsing spreadsheet…';
            statusEl.style.display = '';
            statusEl.style.color = '#64748b';

            const fd = new FormData();
            fd.append('file', fileInput.files[0]);
            fd.append('_token', '{{ csrf_token() }}');

            fetch('{{ route("church-envelopes.parse") }}', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(data => {
                    if (!data.success) throw new Error(data.error || 'Unknown error');

                    document.querySelector('[name="church"]').value    = data.church    ?? '';
                    document.querySelector('[name="town"]').value      = data.town      ?? '';
                    document.querySelector('[name="diocese_1"]').value = data.diocese_1 ?? '';
                    document.querySelector('[name="diocese_2"]').value = data.diocese_2 ?? '';
                    document.querySelector('[name="diocese_3"]').value = data.diocese_3 ?? '';
                    document.getElementById('start_date').value        = data.start_date  ?? '';
                    document.getElementById('num_weeks').value         = data.num_weeks   ?? 52;
                    document.getElementById('set_numbers').value       = data.set_numbers ?? '';
                    document.getElementById('none_copies').value       = data.none_copies ?? 0;
                    const setupEl = document.getElementById('include_setup');
                    if (setupEl) setupEl.checked = !!data.include_setup;

                    (data.vts || []).forEach((val, i) => {
                        const el = document.getElementById('vt-' + (i + 1));
                        if (el) el.value = val ?? '';
                    });
                    document.getElementById('verse-select').value = 'custom';

                    const designPath = document.getElementById('design_path');
                    if (designPath) designPath.value = data.design_path ?? '';
                    const designPreset = document.getElementById('design-preset');
                    if (designPreset) designPreset.value = data.design_path ?? '';

                    document.getElementById('specials-list').innerHTML = '';
                    document.getElementById('no-specials-msg').style.display = '';
                    specialIndex = 0;
                    (data.specials || []).forEach(s => {
                        addSpecial();
                        const rows = document.querySelectorAll('.special-row');
                        const row  = rows[rows.length - 1];
                        row.querySelector('[name$="[name]"]').value = s.name ?? '';
                        row.querySelector('[name$="[date]"]').value = s.date ?? '';
                        row.querySelector('[name$="[vt7]"]').value  = s.vt7  ?? '';
                        const showDate = row.querySelector('[name$="[show_date]"]');
                        if (showDate) showDate.checked = !!s.show_date;
                        const pos = row.querySelector('[name$="[position]"]');
                        if (pos) pos.value = s.position ?? 'after';
                    });
                    if ((data.specials || []).length > 0) {
                        document.getElementById('no-specials-msg').style.display = 'none';
                    }

                    updateWeeksPreview();
                    document.getElementById('set_numbers').dispatchEvent(new Event('input'));

                    statusEl.textContent = 'Fields loaded. Update the start date for the new year.';
                    statusEl.style.color = '#16a34a';
                })
                .catch(err => {
                    statusEl.textContent = 'Error: ' + err.message;
                    statusEl.style.color = '#dc2626';
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.textContent = 'Load from file';
                });
        }

        // Default to V4 on fresh load (no old() values)
        @unless(old('vt'))
        @php $v4 = $verses->firstWhere('label', 'V4'); @endphp
        @if($v4)
        document.getElementById('verse-select').value = 'v{{ $v4->id }}';
        applyVerse('v{{ $v4->id }}');
        @endif
        @endunless
    </script>
</x-layout>
