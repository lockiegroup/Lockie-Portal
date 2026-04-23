<x-layout title="Policy Settings — Lockie Portal">

    <main style="max-width:860px;margin:0 auto;padding:2rem 1rem;">

        <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.75rem;">
            <div>
                <h1 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 0.25rem;">Policy Settings</h1>
                <p style="font-size:0.875rem;color:#64748b;margin:0;">Upload and manage company policy documents.</p>
            </div>
            <button onclick="openUploadModal()"
                style="padding:0.5rem 1.1rem;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:0.875rem;font-weight:600;cursor:pointer;">
                + Upload Policy
            </button>
        </div>

        @if(session('success'))
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;padding:0.75rem 1rem;border-radius:8px;font-size:0.875rem;margin-bottom:1.25rem;">
            {{ session('success') }}
        </div>
        @endif

        @if($policies->isEmpty())
            <div style="text-align:center;padding:4rem 1rem;background:#fff;border-radius:12px;border:1px solid #e2e8f0;">
                <p style="color:#64748b;font-size:0.875rem;">No policies uploaded yet. Click <strong>Upload Policy</strong> to get started.</p>
            </div>
        @else
        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
            <table style="width:100%;border-collapse:collapse;font-size:0.875rem;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                        <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#374151;">Title</th>
                        <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#374151;">Category</th>
                        <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#374151;">File</th>
                        <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#374151;">Uploaded</th>
                        <th style="padding:0.75rem 1rem;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($policies as $policy)
                    <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:0.75rem 1rem;font-weight:500;color:#0f172a;">
                            {{ $policy->title }}
                            @if($policy->description)
                            <p style="font-size:0.75rem;color:#64748b;margin:0.1rem 0 0;font-weight:400;">{{ Str::limit($policy->description, 60) }}</p>
                            @endif
                        </td>
                        <td style="padding:0.75rem 1rem;color:#64748b;">{{ $policy->category ?: '—' }}</td>
                        <td style="padding:0.75rem 1rem;">
                            <a href="{{ route('policies.download', $policy) }}" target="_blank"
                               style="color:#2563eb;font-size:0.78rem;text-decoration:none;display:inline-flex;align-items:center;gap:4px;"
                               onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                <svg style="width:12px;height:12px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                    <polyline points="14 2 14 8 20 8"/>
                                </svg>
                                {{ Str::limit($policy->file_name, 30) }}
                            </a>
                        </td>
                        <td style="padding:0.75rem 1rem;color:#94a3b8;font-size:0.78rem;white-space:nowrap;">{{ $policy->created_at->format('d M Y') }}</td>
                        <td style="padding:0.75rem 1rem;text-align:right;white-space:nowrap;">
                            <button onclick="openEditModal({{ $policy->id }}, {{ json_encode($policy->title) }}, {{ json_encode($policy->category) }}, {{ json_encode($policy->description) }})"
                                style="background:none;border:1px solid #e2e8f0;border-radius:6px;padding:4px 10px;font-size:0.75rem;font-weight:500;color:#374151;cursor:pointer;margin-right:4px;"
                                onmouseover="this.style.borderColor='#94a3b8'" onmouseout="this.style.borderColor='#e2e8f0'">
                                Edit
                            </button>
                            <form action="{{ route('admin.policies.destroy', $policy) }}" method="POST" style="display:inline;" onsubmit="return confirm('Delete this policy? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    style="background:none;border:1px solid #fecaca;border-radius:6px;padding:4px 10px;font-size:0.75rem;font-weight:500;color:#dc2626;cursor:pointer;"
                                    onmouseover="this.style.background='#fef2f2'" onmouseout="this.style.background='none'">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

    </main>

    {{-- Upload Modal --}}
    <div id="upload-modal" style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;padding:1rem;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.45);" onclick="closeUploadModal()"></div>
        <div style="position:relative;background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
            <h2 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 1.25rem;">Upload Policy</h2>

            <form action="{{ route('admin.policies.store') }}" method="POST" enctype="multipart/form-data">
                @csrf

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Title <span style="color:#dc2626;">*</span></label>
                    <input type="text" name="title" required placeholder="e.g. Health & Safety Policy"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Category <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <input type="text" name="category" list="category-list" placeholder="e.g. HR, Health & Safety…"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                    <datalist id="category-list">
                        @foreach($categories as $cat)
                        <option value="{{ $cat }}">
                        @endforeach
                    </datalist>
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Description <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea name="description" rows="2" placeholder="Brief description of this policy…"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;resize:vertical;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">PDF File <span style="color:#dc2626;">*</span></label>
                    <input type="file" name="file" accept=".pdf" required
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;background:#f8fafc;cursor:pointer;">
                    <p style="font-size:0.72rem;color:#94a3b8;margin:4px 0 0;">PDF only, max 20 MB</p>
                </div>

                <div style="display:flex;gap:0.5rem;">
                    <button type="button" onclick="closeUploadModal()"
                        style="flex:1;padding:0.5rem;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.875rem;font-weight:500;border-radius:8px;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit"
                        style="flex:2;padding:0.5rem;background:#0f172a;color:#fff;font-size:0.875rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Modal --}}
    <div id="edit-modal" style="display:none;position:fixed;inset:0;z-index:50;align-items:center;justify-content:center;padding:1rem;">
        <div style="position:absolute;inset:0;background:rgba(15,23,42,0.45);" onclick="closeEditModal()"></div>
        <div style="position:relative;background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:480px;box-shadow:0 20px 60px rgba(0,0,0,0.15);max-height:90vh;overflow-y:auto;">
            <h2 style="font-size:1rem;font-weight:700;color:#0f172a;margin:0 0 1.25rem;">Edit Policy</h2>

            <form id="edit-form" method="POST" enctype="multipart/form-data">
                @csrf @method('PUT')

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Title <span style="color:#dc2626;">*</span></label>
                    <input type="text" id="edit-title" name="title" required
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Category <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <input type="text" id="edit-category" name="category" list="category-list"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'">
                </div>

                <div style="margin-bottom:1rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Description <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
                    <textarea id="edit-description" name="description" rows="2"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;outline:none;resize:vertical;"
                        onfocus="this.style.borderColor='#3b82f6'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
                </div>

                <div style="margin-bottom:1.5rem;">
                    <label style="display:block;font-size:0.75rem;font-weight:600;color:#374151;margin-bottom:5px;">Replace PDF <span style="color:#94a3b8;font-weight:400;">(leave blank to keep existing)</span></label>
                    <input type="file" name="file" accept=".pdf"
                        style="width:100%;padding:0.5rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;box-sizing:border-box;background:#f8fafc;cursor:pointer;">
                    <p style="font-size:0.72rem;color:#94a3b8;margin:4px 0 0;">PDF only, max 20 MB</p>
                </div>

                <div style="display:flex;gap:0.5rem;">
                    <button type="button" onclick="closeEditModal()"
                        style="flex:1;padding:0.5rem;border:1px solid #e2e8f0;background:#f8fafc;color:#374151;font-size:0.875rem;font-weight:500;border-radius:8px;cursor:pointer;">
                        Cancel
                    </button>
                    <button type="submit"
                        style="flex:2;padding:0.5rem;background:#0f172a;color:#fff;font-size:0.875rem;font-weight:600;border-radius:8px;border:none;cursor:pointer;">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openUploadModal() { document.getElementById('upload-modal').style.display = 'flex'; }
    function closeUploadModal() { document.getElementById('upload-modal').style.display = 'none'; }

    function openEditModal(id, title, category, description) {
        document.getElementById('edit-form').action = `/admin/policies/${id}`;
        document.getElementById('edit-title').value       = title || '';
        document.getElementById('edit-category').value    = category || '';
        document.getElementById('edit-description').value = description || '';
        document.getElementById('edit-modal').style.display = 'flex';
    }
    function closeEditModal() { document.getElementById('edit-modal').style.display = 'none'; }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') { closeUploadModal(); closeEditModal(); }
    });

    @if($errors->any())
    openUploadModal();
    @endif
    </script>

</x-layout>
