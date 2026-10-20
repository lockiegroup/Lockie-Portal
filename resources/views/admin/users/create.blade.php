<x-layout title="Add Staff Member — Lockie Portal">

    <main class="max-w-xl mx-auto px-6 py-10">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">Add Staff Member</h1>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8">
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-5">
                @csrf

                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Full name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>

                <div class="bg-sky-50 border border-sky-200 rounded-[0.625rem] px-4 py-3">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="tablet_only" id="tablet-only" value="1"
                            class="w-4 h-4 accent-sky-700 cursor-pointer"
                            {{ old('tablet_only') ? 'checked' : '' }}
                            onchange="toggleTabletOnly(this.checked)">
                        <span class="text-sm font-medium text-sky-700">Tablet only — PIN login, no web access</span>
                    </label>
                </div>

                <div id="email-field">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
                    <input type="email" name="email" id="email-input" value="{{ old('email') }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Role</label>
                    <select name="role" id="role-select"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition"
                        onchange="togglePermissions(this.value)">
                        <option value="staff" {{ old('role', 'staff') === 'staff' ? 'selected' : '' }}>Staff</option>
                        @if(auth()->user()->isMaster())
                            <option value="master" {{ old('role') === 'master' ? 'selected' : '' }}>Master — full access</option>
                        @endif
                    </select>
                </div>

                {{-- Staff controls (hidden for master) --}}
                <div id="staff-controls" style="{{ old('role') === 'master' ? 'display:none;' : '' }}">

                    <div class="mb-5">
                        <label class="block text-sm font-medium text-slate-700 mb-2">Admin Tools</label>
                        <div class="bg-slate-50 border border-slate-200 rounded-[0.625rem] px-4 py-3 flex flex-col gap-2">
                            @foreach($permissions as $key => $label)
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="perm_{{ $key }}" value="1"
                                        class="w-4 h-4 accent-sky-700 cursor-pointer"
                                        {{ old('perm_' . $key) ? 'checked' : '' }}>
                                    <span class="text-sm text-slate-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5">Grant access to admin sections. Leave all unticked for a standard staff account.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Module Visibility</label>
                        <div class="bg-slate-50 border border-slate-200 rounded-[0.625rem] px-4 py-3 grid grid-cols-2 gap-2">
                            @foreach($modules as $key => $label)
                                <label class="flex items-center gap-2.5 cursor-pointer">
                                    <input type="checkbox" name="mod_{{ $key }}" value="1"
                                        class="w-4 h-4 accent-green-600 cursor-pointer"
                                        {{ old('mod_' . $key) !== null ? (old('mod_' . $key) ? 'checked' : '') : 'checked' }}>
                                    <span class="text-sm text-slate-700">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-slate-400 mt-1.5">Untick any modules this staff member should not see.</p>
                    </div>

                </div>

                <div id="password-field">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Temporary password</label>
                    <input type="password" name="password" id="password-input"
                        placeholder="Min 8 chars, uppercase, number"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    <p class="text-xs text-slate-400 mt-1">They can change this after logging in.</p>
                </div>

                <div class="border-t border-slate-200 pt-5">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">
                        Factory tablet PIN
                        <span id="pin-optional" class="text-slate-400 font-normal">(optional, 6–8 digits)</span>
                        <span id="pin-required" class="text-red-500 font-normal hidden">(required, 6–8 digits)</span>
                    </label>
                    <input type="text" name="operator_pin" id="operator-pin" inputmode="numeric" pattern="[0-9]*" maxlength="8"
                        placeholder="e.g. 1234"
                        value="{{ old('operator_pin') }}"
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition">
                    <p class="text-xs text-slate-400 mt-1">Used to sign in on the factory machine tablet view.</p>
                </div>

                <button type="submit"
                    class="w-full bg-slate-900 hover:bg-slate-700 text-white font-semibold py-3 rounded-lg transition-colors">
                    Add Staff Member
                </button>
            </form>
        </div>
    </main>

    <script>
    function togglePermissions(role) {
        document.getElementById('staff-controls').style.display = role === 'master' ? 'none' : '';
    }
    function toggleTabletOnly(tabletOnly) {
        const emailInput    = document.getElementById('email-input');
        const passwordInput = document.getElementById('password-input');
        document.getElementById('email-field').style.display    = tabletOnly ? 'none' : '';
        document.getElementById('password-field').style.display = tabletOnly ? 'none' : '';
        emailInput.required    = !tabletOnly;
        emailInput.disabled    = tabletOnly;
        if (tabletOnly) emailInput.value = '';
        passwordInput.required = !tabletOnly;
        passwordInput.disabled = tabletOnly;
        document.getElementById('operator-pin').required      = tabletOnly;
        document.getElementById('pin-optional').style.display = tabletOnly ? 'none' : '';
        document.getElementById('pin-required').style.display = tabletOnly ? '' : 'none';
    }
    const tabletCb = document.getElementById('tablet-only');
    if (tabletCb.checked) toggleTabletOnly(true);
    </script>
</x-layout>
