<x-layout title="Envelope Settings — Lockie Portal">
    <nav class="bg-slate-900 shadow-lg">
        <div class="max-w-5xl mx-auto px-6 py-4 flex items-center justify-between">
            <a href="{{ route('dashboard') }}">
                <img src="{{ asset('images/logo.png') }}" alt="Lockie Group" class="h-12 w-auto">
            </a>
            <a href="{{ route('dashboard') }}" class="text-slate-400 hover:text-white text-sm transition-colors">← Dashboard</a>
        </div>
    </nav>

    <main class="max-w-5xl mx-auto px-6 py-10">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-slate-800">Envelope Settings</h1>
            <p class="text-slate-500 mt-1">Manage envelope designs and verse library.</p>
        </div>

        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- ================================================================ --}}
        {{-- GLOBAL SETTINGS                                                  --}}
        {{-- ================================================================ --}}
        <section class="mb-12">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Global Settings</h2>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <form action="{{ route('admin.envelope-settings.spiral-path.update') }}" method="POST">
                    @csrf
                    <div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
                        <div style="flex:1;min-width:260px;">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Spiral Image Path</label>
                            <p class="text-xs text-slate-400 mb-2">Used for special envelopes (e.g. Christmas, Easter). Leave blank to omit image.</p>
                            <input type="text" name="spiral_image_path"
                                value="{{ $spiralPath }}"
                                placeholder="e.g. Lockie iMac HD:Users:design:Envelopes:Spiral.tif"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        </div>
                        <div>
                            <button type="submit"
                                class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                Save
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </section>

        {{-- ================================================================ --}}
        {{-- DESIGNS SECTION                                                  --}}
        {{-- ================================================================ --}}
        <section class="mb-12">
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Designs</h2>

            {{-- Add Design --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                <h3 class="font-medium text-slate-700 mb-4">Add Design</h3>
                <form action="{{ route('admin.envelope-settings.designs.store') }}" method="POST">
                    @csrf
                    <div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
                        <div style="flex:1;min-width:160px;">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Name <span class="text-red-500">*</span></label>
                            <input type="text" name="name" required
                                placeholder="e.g. Gold"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        </div>
                        <div style="flex:3;min-width:260px;">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">File Path <span class="text-red-500">*</span></label>
                            <input type="text" name="path" required
                                placeholder="e.g. Lockie iMac HD:Users:design:Envelopes:Gold.tif"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        </div>
                        <div>
                            <button type="submit"
                                class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                Add Design
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Existing Designs --}}
            @if($designs->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-3 py-3 w-8"></th>
                            <th class="text-left px-6 py-3 font-semibold text-slate-600">Name</th>
                            <th class="text-left px-6 py-3 font-semibold text-slate-600">Path</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="designs-sortable" class="divide-y divide-slate-100">
                        @foreach($designs as $design)
                            <tr class="hover:bg-slate-50 transition-colors" data-id="{{ $design->id }}">
                                <td class="px-3 py-4 text-slate-300 cursor-grab active:cursor-grabbing design-handle">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg>
                                </td>
                                <td class="px-6 py-4 font-medium text-slate-800">{{ $design->name }}</td>
                                <td class="px-6 py-4 text-slate-500 max-w-xs">
                                    <span title="{{ $design->path }}" class="block truncate">{{ $design->path }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div style="display:flex;justify-content:flex-end;gap:12px;align-items:center;">
                                        <button type="button"
                                            onclick="toggleEdit('edit-design-{{ $design->id }}')"
                                            class="text-sky-600 hover:text-sky-800 font-medium">Edit</button>
                                        <form action="{{ route('admin.envelope-settings.designs.destroy', $design) }}" method="POST"
                                            onsubmit="return confirm('Delete design {{ addslashes($design->name) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr id="edit-design-{{ $design->id }}" style="display:none;">
                                <td colspan="4" class="px-6 py-4 bg-slate-50 border-b border-slate-200">
                                    <form action="{{ route('admin.envelope-settings.designs.update', $design) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div style="display:flex;gap:16px;align-items:flex-end;flex-wrap:wrap;">
                                            <div style="flex:1;min-width:140px;">
                                                <label class="block text-xs font-medium text-slate-600 mb-1">Name</label>
                                                <input type="text" name="name" required value="{{ $design->name }}"
                                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                            </div>
                                            <div style="flex:3;min-width:240px;">
                                                <label class="block text-xs font-medium text-slate-600 mb-1">Path</label>
                                                <input type="text" name="path" required value="{{ $design->path }}"
                                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                            </div>
                                            <div style="display:flex;gap:8px;">
                                                <button type="submit"
                                                    class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                                    Save
                                                </button>
                                                <button type="button"
                                                    onclick="toggleEdit('edit-design-{{ $design->id }}')"
                                                    class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold rounded-lg transition-colors">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <p class="text-sm text-slate-400 italic">No designs added yet.</p>
            @endif
        </section>

        {{-- ================================================================ --}}
        {{-- VERSES SECTION                                                   --}}
        {{-- ================================================================ --}}
        <section>
            <h2 class="text-lg font-semibold text-slate-800 mb-4">Verse Library</h2>

            {{-- Add Verse --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6">
                <h3 class="font-medium text-slate-700 mb-4">Add Verse</h3>
                <form action="{{ route('admin.envelope-settings.verses.store') }}" method="POST">
                    @csrf
                    <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:12px;">
                        <div style="width:120px;">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Label <span class="text-red-500">*</span></label>
                            <input type="text" name="label" required maxlength="10"
                                placeholder="e.g. V25"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        </div>
                        <div style="width:100px;">
                            <label class="block text-sm font-medium text-slate-700 mb-1.5">Sort Order</label>
                            <input type="number" name="sort_order" value="0"
                                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-4">
                        @for($i = 0; $i < 8; $i++)
                            <div>
                                <label class="block text-xs font-medium text-slate-500 mb-1">Line {{ $i + 1 }}</label>
                                <input type="text" name="lines[]" value=""
                                    placeholder="Line {{ $i + 1 }}"
                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                            </div>
                        @endfor
                    </div>
                    <button type="submit"
                        class="px-5 py-2.5 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                        Add Verse
                    </button>
                </form>
            </div>

            {{-- Existing Verses --}}
            @if($verses->isNotEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-3 py-3 w-8"></th>
                            <th class="text-left px-6 py-3 font-semibold text-slate-600 w-24">Label</th>
                            <th class="text-left px-6 py-3 font-semibold text-slate-600">Preview</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody id="verses-sortable" class="divide-y divide-slate-100">
                        @foreach($verses as $verse)
                            <tr class="hover:bg-slate-50 transition-colors" data-id="{{ $verse->id }}">
                                <td class="px-3 py-4 text-slate-300 cursor-grab active:cursor-grabbing verse-handle">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg>
                                </td>
                                <td class="px-6 py-4 font-mono font-semibold text-slate-800">{{ $verse->label }}</td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ collect($verse->lines)->first(fn($l) => $l !== '') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div style="display:flex;justify-content:flex-end;gap:12px;align-items:center;">
                                        <button type="button"
                                            onclick="toggleEdit('edit-verse-{{ $verse->id }}')"
                                            class="text-sky-600 hover:text-sky-800 font-medium">Edit</button>
                                        <form action="{{ route('admin.envelope-settings.verses.destroy', $verse) }}" method="POST"
                                            onsubmit="return confirm('Delete verse {{ addslashes($verse->label) }}?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr id="edit-verse-{{ $verse->id }}" style="display:none;">
                                <td colspan="4" class="px-6 py-4 bg-slate-50 border-b border-slate-200" style="padding-left:3rem;">
                                    <form action="{{ route('admin.envelope-settings.verses.update', $verse) }}" method="POST">
                                        @csrf @method('PUT')
                                        <div style="display:flex;gap:16px;align-items:flex-start;flex-wrap:wrap;margin-bottom:12px;">
                                            <div style="width:120px;">
                                                <label class="block text-xs font-medium text-slate-600 mb-1">Label</label>
                                                <input type="text" name="label" required maxlength="10"
                                                    value="{{ $verse->label }}"
                                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                            </div>
                                            <div style="width:100px;">
                                                <label class="block text-xs font-medium text-slate-600 mb-1">Sort Order</label>
                                                <input type="number" name="sort_order"
                                                    value="{{ $verse->sort_order }}"
                                                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3 mb-4">
                                            @for($i = 0; $i < 8; $i++)
                                                <div>
                                                    <label class="block text-xs font-medium text-slate-500 mb-1">Line {{ $i + 1 }}</label>
                                                    <input type="text" name="lines[]"
                                                        value="{{ $verse->lines[$i] ?? '' }}"
                                                        placeholder="Line {{ $i + 1 }}"
                                                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                                                </div>
                                            @endfor
                                        </div>
                                        <div style="display:flex;gap:8px;">
                                            <button type="submit"
                                                class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg transition-colors">
                                                Save
                                            </button>
                                            <button type="button"
                                                onclick="toggleEdit('edit-verse-{{ $verse->id }}')"
                                                class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 text-sm font-semibold rounded-lg transition-colors">
                                                Cancel
                                            </button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
                <p class="text-sm text-slate-400 italic">No verses in the library yet.</p>
            @endif
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
    <script>
        function toggleEdit(id) {
            const row = document.getElementById(id);
            if (!row) return;
            row.style.display = row.style.display === 'none' ? '' : 'none';
        }

        function saveOrder(url, ids) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ids }),
            });
        }

        const designsEl = document.getElementById('designs-sortable');
        if (designsEl) {
            Sortable.create(designsEl, {
                handle: '.design-handle',
                animation: 150,
                onEnd() {
                    const ids = [...designsEl.querySelectorAll('tr[data-id]')].map(r => r.dataset.id);
                    saveOrder('{{ route('admin.envelope-settings.designs.reorder') }}', ids);
                },
            });
        }

        const versesEl = document.getElementById('verses-sortable');
        if (versesEl) {
            Sortable.create(versesEl, {
                handle: '.verse-handle',
                animation: 150,
                onEnd() {
                    const ids = [...versesEl.querySelectorAll('tr[data-id]')].map(r => r.dataset.id);
                    saveOrder('{{ route('admin.envelope-settings.verses.reorder') }}', ids);
                },
            });
        }
    </script>
</x-layout>
