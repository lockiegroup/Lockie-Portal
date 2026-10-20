<x-layout title="Policy Settings — Lockie Portal">

    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

        <div class="flex items-start justify-between flex-wrap gap-4 mb-7">
            <div>
                <h1 class="text-2xl font-bold text-slate-900 mb-1">Policy Settings</h1>
                <p class="text-sm text-slate-500">Upload and manage company policy documents.</p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <button onclick="openCatModal()"
                    class="px-4 py-2 bg-white text-slate-700 border border-slate-200 rounded-lg text-sm font-semibold cursor-pointer hover:bg-slate-50 transition-colors">
                    Manage Categories
                </button>
                <button onclick="openUploadModal()"
                    class="px-4 py-2 bg-slate-900 text-white border-0 rounded-lg text-sm font-semibold cursor-pointer hover:bg-slate-800 transition-colors">
                    + Upload Policy
                </button>
            </div>
        </div>

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg text-sm mb-5">
            {{ session('success') }}
        </div>
        @endif

        @if($policies->isEmpty())
            <div class="text-center py-16 px-4 bg-white rounded-xl border border-slate-200">
                <p class="text-slate-500 text-sm">No policies uploaded yet. Click <strong>Upload Policy</strong> to get started.</p>
            </div>
        @else
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm" style="min-width:540px;">
                <thead>
                    <tr class="bg-slate-50 border-b-2 border-slate-200">
                        <th class="px-4 py-3 text-left font-bold text-slate-700">Title</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-700">Category</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-700">File</th>
                        <th class="px-4 py-3 text-left font-bold text-slate-700 whitespace-nowrap">Last Updated</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($policies as $policy)
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-3 font-medium text-slate-900">
                            {{ $policy->title }}
                            @if($policy->description)
                            <p class="text-[0.72rem] text-slate-500 mt-0.5 font-normal">{{ Str::limit($policy->description, 60) }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-500">{{ $policy->category ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('policies.download', $policy) }}" target="_blank"
                               class="text-blue-600 text-[0.78rem] no-underline inline-flex items-center gap-1 hover:underline">
                                <svg class="w-3 h-3 flex-shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                {{ Str::limit($policy->file_name, 30) }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-[0.78rem] whitespace-nowrap text-slate-500">
                            {{ $policy->last_reviewed_at ? $policy->last_reviewed_at->format('d M Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <button onclick="openEditModal({{ $policy->id }}, {{ json_encode($policy->title) }}, {{ json_encode($policy->category) }}, {{ json_encode($policy->description) }}, {{ json_encode($policy->last_reviewed_at?->format('Y-m-d')) }})"
                                class="bg-transparent border border-slate-200 rounded-md px-2.5 py-1 text-[0.75rem] font-medium text-slate-700 cursor-pointer mr-1 hover:border-slate-400 transition-colors">
                                Edit
                            </button>
                            <form action="{{ route('admin.policies.destroy', $policy) }}" method="POST" class="inline" onsubmit="return confirm('Delete this policy? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="bg-transparent border border-red-200 rounded-md px-2.5 py-1 text-[0.75rem] font-medium text-red-600 cursor-pointer hover:bg-red-50 transition-colors">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
        @endif

    </main>

    {{-- Upload Modal --}}
    <div id="upload-modal" style="display:none;" class="fixed inset-0 z-50 items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/45" onclick="closeUploadModal()"></div>
        <div class="relative bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">
            <h2 class="text-base font-bold text-slate-900 mb-5">Upload Policy</h2>

            <form action="{{ route('admin.policies.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div class="mb-4">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">Title <span class="text-red-600">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Health & Safety Policy"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>

                <div class="mb-4">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">
                        Category <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <select name="category"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-900 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="">— No category —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                    @if($categories->isEmpty())
                    <p class="text-[0.72rem] text-slate-400 mt-1">No categories yet — click <strong>Manage Categories</strong> to add some.</p>
                    @endif
                </div>

                <div class="mb-4">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea name="description" rows="2" placeholder="Brief description of this policy…"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none resize-y focus:ring-2 focus:ring-sky-500 focus:border-transparent"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">Last Updated <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="date" name="last_reviewed_at"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    <p class="text-[0.72rem] text-slate-400 mt-1">The date this policy was last checked and confirmed up to date.</p>
                </div>

                <div class="mb-6">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">PDF File <span class="text-red-600">*</span></label>
                    <input type="file" name="file" accept=".pdf" required
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 cursor-pointer">
                    <p class="text-[0.72rem] text-slate-400 mt-1">PDF only, max 20 MB</p>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="closeUploadModal()"
                        class="flex-1 py-2 border border-slate-200 bg-slate-50 text-slate-700 text-sm font-medium rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-[2] py-2 bg-slate-900 text-white text-sm font-semibold rounded-lg border-0 cursor-pointer hover:bg-slate-800 transition-colors">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="edit-modal" style="display:none;" class="fixed inset-0 z-50 items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/45" onclick="closeEditModal()"></div>
        <div class="relative bg-white rounded-2xl p-6 w-full max-w-lg shadow-2xl max-h-[90vh] overflow-y-auto">
            <h2 class="text-base font-bold text-slate-900 mb-5">Edit Policy</h2>

            <form id="edit-form" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div class="mb-4">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">Title <span class="text-red-600">*</span></label>
                    <input type="text" id="edit-title" name="title" required
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>

                <div class="mb-4">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">
                        Category <span class="text-slate-400 font-normal">(optional)</span>
                    </label>
                    <select id="edit-category" name="category"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white text-slate-900 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="">— No category —</option>
                        @foreach($categories as $cat)
                        <option value="{{ $cat->name }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-4">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">Description <span class="text-slate-400 font-normal">(optional)</span></label>
                    <textarea id="edit-description" name="description" rows="2"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none resize-y focus:ring-2 focus:ring-sky-500 focus:border-transparent"></textarea>
                </div>

                <div class="mb-4">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">Last Updated <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="date" id="edit-reviewed" name="last_reviewed_at"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>

                <div class="mb-6">
                    <label class="block text-[0.75rem] font-semibold text-slate-700 mb-1.5">Replace PDF <span class="text-slate-400 font-normal">(leave blank to keep existing)</span></label>
                    <input type="file" name="file" accept=".pdf"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-slate-50 cursor-pointer">
                    <p class="text-[0.72rem] text-slate-400 mt-1">PDF only, max 20 MB</p>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="closeEditModal()"
                        class="flex-1 py-2 border border-slate-200 bg-slate-50 text-slate-700 text-sm font-medium rounded-lg cursor-pointer hover:bg-slate-100 transition-colors">
                        Cancel
                    </button>
                    <button type="submit"
                        class="flex-[2] py-2 bg-slate-900 text-white text-sm font-semibold rounded-lg border-0 cursor-pointer hover:bg-slate-800 transition-colors">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Manage Categories Modal --}}
    <div id="cat-modal" style="display:none;" class="fixed inset-0 z-50 items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/45" onclick="closeCatModal()"></div>
        <div class="relative bg-white rounded-2xl p-6 w-full max-w-md shadow-2xl max-h-[85vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-5">
                <h2 class="text-base font-bold text-slate-900">Manage Categories</h2>
                <button onclick="closeCatModal()" class="bg-transparent border-0 cursor-pointer text-slate-400 p-1 leading-none hover:text-slate-700">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <ul id="cat-list" class="list-none p-0 mb-4">
                @forelse($categories as $cat)
                <li id="cat-item-{{ $cat->id }}" class="flex items-center justify-between px-3 py-2 border border-slate-200 rounded-lg mb-1.5 bg-slate-50">
                    <span class="text-sm text-slate-900 font-medium">{{ $cat->name }}</span>
                    <button onclick="deleteCategory({{ $cat->id }})"
                        class="bg-transparent border-0 cursor-pointer text-slate-400 px-1.5 py-0.5 text-[0.75rem] rounded hover:text-red-600 transition-colors">Remove</button>
                </li>
                @empty
                <li id="cat-empty" class="p-4 text-center text-slate-400 text-sm">No categories yet.</li>
                @endforelse
            </ul>

            <div class="flex gap-2">
                <input type="text" id="new-cat-name" placeholder="New category name…"
                    class="flex-1 px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                    onkeydown="if(event.key==='Enter'){addCategory();}">
                <button onclick="addCategory()"
                    class="px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-lg border-0 cursor-pointer whitespace-nowrap hover:bg-slate-800 transition-colors">
                    + Add
                </button>
            </div>
            <p class="text-[0.72rem] text-slate-400 mt-2">Removing a category doesn't delete the policies — they just become uncategorised.</p>
        </div>
    </div>

    <script>
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    function openUploadModal()  { document.getElementById('upload-modal').style.display = 'flex'; }
    function closeUploadModal() { document.getElementById('upload-modal').style.display = 'none'; }

    function openEditModal(id, title, category, description, reviewedAt) {
        document.getElementById('edit-form').action = `/admin/policies/${id}`;
        document.getElementById('edit-title').value       = title || '';
        document.getElementById('edit-description').value = description || '';
        document.getElementById('edit-reviewed').value    = reviewedAt || '';
        const sel = document.getElementById('edit-category');
        sel.value = category || '';
        document.getElementById('edit-modal').style.display = 'flex';
    }
    function closeEditModal() { document.getElementById('edit-modal').style.display = 'none'; }

    function openCatModal()  { document.getElementById('cat-modal').style.display = 'flex'; }
    function closeCatModal() { document.getElementById('cat-modal').style.display = 'none'; }

    function refreshCategorySelects(name, id) {
        ['upload-modal', 'edit-modal'].forEach(modalId => {
            const sel = document.querySelector(`#${modalId} select[name="category"]`);
            if (!sel) return;
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            opt.dataset.catId = id;
            sel.appendChild(opt);
        });
    }

    async function addCategory() {
        const input = document.getElementById('new-cat-name');
        const name  = input.value.trim();
        if (!name) return;

        const res = await fetch('{{ route('admin.policies.categories.store') }}', {
            method : 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body   : JSON.stringify({ name }),
        });

        if (res.ok) {
            const data = await res.json();
            const list = document.getElementById('cat-list');
            const empty = document.getElementById('cat-empty');
            if (empty) empty.remove();

            const li = document.createElement('li');
            li.id = `cat-item-${data.id}`;
            li.className = 'flex items-center justify-between px-3 py-2 border border-slate-200 rounded-lg mb-1.5 bg-slate-50';
            li.innerHTML = `<span class="text-sm text-slate-900 font-medium">${data.name}</span>
                <button onclick="deleteCategory(${data.id})" class="bg-transparent border-0 cursor-pointer text-slate-400 px-1.5 py-0.5 text-xs rounded hover:text-red-600 transition-colors">Remove</button>`;
            list.appendChild(li);

            refreshCategorySelects(data.name, data.id);
            input.value = '';
        } else {
            const err = await res.json();
            alert(err.message || 'Could not add category.');
        }
    }

    async function deleteCategory(id) {
        if (!confirm('Remove this category?')) return;
        const res = await fetch(`/admin/policies/categories/${id}`, {
            method : 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        if (res.ok) {
            const el = document.getElementById(`cat-item-${id}`);
            if (el) el.remove();
            if (!document.querySelector('#cat-list li')) {
                const li = document.createElement('li');
                li.id = 'cat-empty';
                li.className = 'p-4 text-center text-slate-400 text-sm';
                li.textContent = 'No categories yet.';
                document.getElementById('cat-list').appendChild(li);
            }
            document.querySelectorAll('select[name="category"] option').forEach(opt => {
                if (opt.dataset.catId == id) opt.remove();
            });
        }
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeUploadModal(); closeEditModal(); closeCatModal(); }
    });

    @if($errors->any())
    openUploadModal();
    @endif
    </script>

</x-layout>
