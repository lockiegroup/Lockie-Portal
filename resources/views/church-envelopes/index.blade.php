<x-layout title="Church Envelope Generator — Lockie Portal">
    <nav class="bg-slate-900 shadow-lg">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" class="h-12 w-auto">
            </a>
            <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white text-sm transition-colors">← Dashboard</a>
        </div>
    </nav>

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
            </div>

            {{-- Set Numbers --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="font-semibold text-slate-800">Set Numbers</h2>

                <div style="display:flex;gap:12px;flex-wrap:wrap;">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="set_number_type" value="sequential"
                            {{ old('set_number_type', 'sequential') === 'sequential' ? 'checked' : '' }}
                            onchange="toggleSetType('sequential')" class="text-sky-600">
                        <span class="text-sm font-medium text-slate-700">Sequential</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="set_number_type" value="custom"
                            {{ old('set_number_type') === 'custom' ? 'checked' : '' }}
                            onchange="toggleSetType('custom')" class="text-sky-600">
                        <span class="text-sm font-medium text-slate-700">Custom / ranges</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="set_number_type" value="none"
                            {{ old('set_number_type') === 'none' ? 'checked' : '' }}
                            onchange="toggleSetType('none')" class="text-sky-600">
                        <span class="text-sm font-medium text-slate-700">No set numbers</span>
                    </label>
                </div>

                {{-- Sequential --}}
                <div id="sequential-section" class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Starting number</label>
                        <input type="number" name="seq_start" value="{{ old('seq_start', 1) }}" min="1"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">How many sets</label>
                        <input type="number" name="seq_count" value="{{ old('seq_count', 20) }}" min="1"
                            id="seq_count"
                            class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                </div>

                {{-- Custom / ranges --}}
                <div id="custom-section" class="hidden">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Set numbers
                        <span class="text-slate-400 font-normal text-xs">— individual numbers, ranges (1-50), or a mix e.g. <code>1-50, 75, 100-110</code></span>
                    </label>
                    <textarea name="custom_numbers" rows="4" id="custom_numbers"
                        placeholder="e.g. 1-50, 75, 100-110&#10;or one per line"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition resize-none font-mono text-sm">{{ old('custom_numbers') }}</textarea>
                    <p id="custom-count" class="text-xs text-slate-400 mt-1"></p>
                </div>

                {{-- No set numbers --}}
                <div id="none-section" class="hidden">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Number of copies to print
                    </label>
                    <input type="number" name="none_copies" id="none_copies"
                        value="{{ old('none_copies', 1) }}" min="1"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    <p class="text-xs text-slate-400 mt-1">Set number columns will be left blank.</p>
                </div>
            </div>

            {{-- Special Envelopes --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-slate-800">Special Envelopes</h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Added to every box set. Date used for ordering — tick to also print it on the envelope.
                            VT1–VT5 are left blank; VT6 = title; VT7 = e.g. Offering or Thanks Giving.
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
                                <div class="grid grid-cols-2 gap-3 items-center">
                                    <div>
                                        <label class="block text-xs font-medium text-slate-500 mb-1">VT7 (e.g. Offering)</label>
                                        <input type="text" name="specials[{{ $i }}][vt7]"
                                            placeholder="Offering or Thanks Giving"
                                            value="{{ $s['vt7'] ?? '' }}"
                                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                    </div>
                                    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:1.25rem;">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="specials[{{ $i }}][show_date]" value="1"
                                                {{ !empty($s['show_date']) ? 'checked' : '' }}
                                                class="rounded">
                                            <span class="text-sm text-slate-600">Show date on envelope</span>
                                        </label>
                                        <button type="button" onclick="this.closest('.special-row').remove(); updateSummary();"
                                            class="text-slate-400 hover:text-red-500 transition-colors text-xl leading-none">&times;</button>
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

            {{-- Variable Text (weekly envelopes) --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="font-semibold text-slate-800">Variable Text — Weekly Envelopes</h2>
                <p class="text-xs text-slate-400">VT1–VT8 used for weekly envelopes. Special envelopes use VT6 (title) and VT7 (offering text) only.</p>
                <div class="grid grid-cols-2 gap-3">
                    @php
                        $vtDefaults = ['In Thanksgiving to God', 'and for the work of', 'His Church', '', '', '', '', ''];
                    @endphp
                    @for($v = 1; $v <= 8; $v++)
                        <div>
                            <label class="block text-xs font-medium text-slate-500 mb-1">VT{{ $v }}</label>
                            <input type="text" name="vt[{{ $v }}]"
                                value="{{ old('vt.' . $v, $vtDefaults[$v - 1]) }}"
                                placeholder="VT{{ $v }}"
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

        // ── Set type toggle ──────────────────────────────────────────────────
        function toggleSetType(type) {
            document.getElementById('sequential-section').classList.toggle('hidden', type !== 'sequential');
            document.getElementById('custom-section').classList.toggle('hidden', type !== 'custom');
            document.getElementById('none-section').classList.toggle('hidden', type !== 'none');
            updateSummary();
        }
        const currentType = document.querySelector('input[name="set_number_type"]:checked')?.value || 'sequential';
        toggleSetType(currentType);

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

        // ── Custom numbers parse (supports ranges) ───────────────────────────
        function parseCustomNumbers(raw) {
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

        document.getElementById('custom_numbers').addEventListener('input', function () {
            const nums = parseCustomNumbers(this.value);
            document.getElementById('custom-count').textContent =
                nums.length + ' set number' + (nums.length === 1 ? '' : 's') + ' detected.';
            updateSummary();
        });

        document.getElementById('seq_count').addEventListener('input', updateSummary);
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
                <div class="grid grid-cols-2 gap-3 items-center">
                    <div>
                        <label class="block text-xs font-medium text-slate-500 mb-1">VT7 (e.g. Offering)</label>
                        <input type="text" name="specials[${i}][vt7]" placeholder="Offering or Thanks Giving"
                            class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    </div>
                    <div style="display:flex;align-items:center;justify-content:space-between;padding-top:1.25rem;">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="specials[${i}][show_date]" value="1" checked class="rounded">
                            <span class="text-sm text-slate-600">Show date on envelope</span>
                        </label>
                        <button type="button" onclick="this.closest('.special-row').remove(); updateSummary();"
                            class="text-slate-400 hover:text-red-500 transition-colors text-xl leading-none">&times;</button>
                    </div>
                </div>`;
            document.getElementById('specials-list').appendChild(row);
            updateSummary();
        }

        // ── Summary ──────────────────────────────────────────────────────────
        function updateSummary() {
            const weeks    = parseInt(document.getElementById('num_weeks').value) || 0;
            const specials = document.querySelectorAll('.special-row').length;
            const type     = document.querySelector('input[name="set_number_type"]:checked')?.value;
            let sets = 0;
            if (type === 'sequential') {
                sets = parseInt(document.getElementById('seq_count').value) || 0;
            } else if (type === 'custom') {
                sets = parseCustomNumbers(document.getElementById('custom_numbers').value).length;
            } else {
                sets = parseInt(document.getElementById('none_copies').value) || 0;
            }
            const envsPerSet = weeks + specials;
            const pairs      = Math.ceil(sets / 2);
            const totalRows  = pairs * envsPerSet;
            const el         = document.getElementById('summary');
            if (sets && weeks) {
                const setsLabel = type === 'none' ? sets + ' copies' : sets + ' sets';
                el.textContent = `${setsLabel} × ${envsPerSet} envelopes = ${totalRows.toLocaleString()} rows (${pairs} pairs, 2-up).`;
            } else {
                el.textContent = '';
            }
        }

        updateSummary();
    </script>
</x-layout>
