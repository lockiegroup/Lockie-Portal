<x-layout title="Manage Users — Lockie Portal">

<main class="max-w-screen-xl mx-auto px-6 py-10">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Staff Accounts</h1>
            <p class="text-slate-500 text-sm mt-1">Add, edit, or deactivate staff portal accounts.</p>
        </div>
        <a href="{{ route('admin.users.create') }}"
            class="bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors">
            + Add Staff Member
        </a>
    </div>

    @if(session('success'))
        <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg px-4 py-3">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">
            {{ session('error') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="w-full text-sm" style="min-width:700px;">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Name</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Role</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Modules &amp; Permissions</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Status</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Last Login</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($users as $user)
                @php
                    $allModules   = \App\Models\User::MODULES;
                    $allPerms     = \App\Models\User::PERMISSIONS;
                    $userModules  = $user->modules === null ? array_keys($allModules) : ($user->modules ?? []);
                    $userPerms    = $user->permissions ?? [];
                @endphp
                <tr class="hover:bg-slate-50 transition-colors align-top">
                    <td class="px-5 py-4">
                        <div class="font-medium text-slate-800">{{ $user->name }}</div>
                        <div class="text-xs text-slate-400 mt-0.5">{{ $user->email }}</div>
                    </td>
                    <td class="px-5 py-4">
                        @if($user->role === 'master')
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-600" style="background:#f3e8ff;color:#7c3aed;font-weight:600;">Master</span>
                        @elseif($user->role === 'admin')
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs" style="background:#e0f2fe;color:#0369a1;font-weight:600;">Admin</span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs" style="background:#f1f5f9;color:#475569;font-weight:600;">Staff</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-wrap gap-1">
                            @foreach($allModules as $key => $label)
                                @if(in_array($key, $userModules))
                                <span style="display:inline-block;padding:1px 7px;border-radius:9999px;font-size:0.7rem;font-weight:500;background:#dbeafe;color:#1d4ed8;">{{ $label }}</span>
                                @endif
                            @endforeach
                            @foreach($allPerms as $key => $label)
                                @if(in_array($key, $userPerms))
                                <span style="display:inline-block;padding:1px 7px;border-radius:9999px;font-size:0.7rem;font-weight:500;background:#fef3c7;color:#92400e;">{{ $label }}</span>
                                @endif
                            @endforeach
                            @if(empty($userModules) && empty($userPerms))
                                <span class="text-xs text-slate-300">No access</span>
                            @endif
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-slate-500 text-sm">
                        @if($user->last_login_at)
                            <span title="{{ $user->last_login_at->format('d M Y H:i') }}">
                                {{ $user->last_login_at->diffForHumans() }}
                            </span>
                        @else
                            <span class="text-slate-300">Never</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-sky-600 hover:text-sky-800 font-medium">Edit</a>
                            @if($user->id !== auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                    onsubmit="return confirm('Remove {{ $user->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 font-medium">Remove</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</main>
</x-layout>
