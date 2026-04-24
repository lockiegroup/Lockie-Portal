<x-layout title="Edit Staff Member — Lockie Portal">

    <main class="max-w-xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Edit {{ $user->name }}</h1>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
            <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-5">
                @csrf @method('PUT')

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Full name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Role</label>
                    <select name="role" id="role-select"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                        onchange="togglePermissions(this.value)">
                        <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff — basic access</option>
                        <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin — configurable access</option>
                        @if(auth()->user()->isMaster())
                            <option value="master" {{ old('role', $user->role) === 'master' ? 'selected' : '' }}>Master — full access</option>
                        @endif
                    </select>
                </div>

                {{-- Permissions (only shown for Admin role) --}}
                <div id="permissions-section" style="{{ old('role', $user->role) === 'admin' ? '' : 'display:none;' }}">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Admin Permissions</label>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.625rem;padding:12px 16px;display:flex;flex-direction:column;gap:8px;">
                        @foreach($permissions as $key => $label)
                            @php
                                $checked = old('perm_' . $key) !== null
                                    ? old('perm_' . $key)
                                    : ($user->role === 'admin' ? $user->hasPermission($key) : false);
                            @endphp
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                <input type="checkbox" name="perm_{{ $key }}" value="1"
                                    style="width:16px;height:16px;accent-color:#0369a1;cursor:pointer;"
                                    {{ $checked ? 'checked' : '' }}>
                                <span style="font-size:0.875rem;color:#334155;">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-top:6px;">Select which admin sections this user can access.</p>
                </div>

                {{-- Module visibility (only shown for Staff role) --}}
                <div id="modules-section" style="{{ old('role', $user->role) === 'staff' ? '' : 'display:none;' }}">
                    <label class="block text-sm font-medium text-slate-700 mb-2">Module Access</label>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.625rem;padding:12px 16px;display:grid;grid-template-columns:1fr 1fr;gap:8px;">
                        @foreach($modules as $key => $label)
                            @php
                                $modChecked = old('mod_' . $key) !== null
                                    ? old('mod_' . $key)
                                    : $user->hasModule($key);
                            @endphp
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                                <input type="checkbox" name="mod_{{ $key }}" value="1"
                                    style="width:16px;height:16px;accent-color:#16a34a;cursor:pointer;"
                                    {{ $modChecked ? 'checked' : '' }}>
                                <span style="font-size:0.875rem;color:#334155;">{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p style="font-size:0.75rem;color:#94a3b8;margin-top:6px;">Untick any modules this staff member should not see.</p>
                </div>

                <div class="flex items-center gap-3">
                    <input type="checkbox" name="is_active" id="is_active" value="1" class="rounded" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                    <label for="is_active" class="text-sm font-medium text-slate-700">Account active</label>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">New password <span class="text-slate-400 font-normal">(leave blank to keep current)</span></label>
                    <input type="password" name="password"
                        placeholder="Min 8 chars, uppercase, number"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>

                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-700 text-white font-semibold py-3 rounded-lg transition-colors">
                    Save Changes
                </button>
            </form>
        </div>
    </main>

    <script>
    function togglePermissions(role) {
        document.getElementById('permissions-section').style.display = role === 'admin' ? '' : 'none';
        document.getElementById('modules-section').style.display     = role === 'staff' ? '' : 'none';
    }
    </script>
</x-layout>
