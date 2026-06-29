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

    @php
        $webUsers    = $users->filter(fn($u) => !is_null($u->email));
        $tabletUsers = $users->filter(fn($u) => is_null($u->email));
    @endphp

    {{-- ── Web / Portal Users ── --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <table class="w-full text-sm" style="min-width:640px;">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-6 py-3 font-semibold text-slate-600">Name</th>
                    <th class="text-left px-6 py-3 font-semibold text-slate-600">Email</th>
                    <th class="text-left px-6 py-3 font-semibold text-slate-600">Role</th>
                    <th class="text-left px-6 py-3 font-semibold text-slate-600">Status</th>
                    <th class="text-left px-5 py-3 font-semibold text-slate-600">Last Login</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($webUsers as $user)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-5 py-4 font-medium text-slate-800">{{ $user->name }}</td>
                    <td class="px-5 py-4 text-slate-500">{{ $user->email }}</td>
                    <td class="px-5 py-4">
                        @if($user->role === 'master')
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs" style="background:#f3e8ff;color:#7c3aed;font-weight:600;">Master</span>
                        @elseif($user->role === 'admin')
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs" style="background:#e0f2fe;color:#0369a1;font-weight:600;">Admin</span>
                        @else
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs" style="background:#f1f5f9;color:#475569;font-weight:600;">Staff</span>
                        @endif
                        @if($user->operator_pin)
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs" style="background:#ecfdf5;color:#059669;font-weight:600;">+ Tablet</span>
                        @endif
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
                            @if(!$user->isMaster() && $user->id !== auth()->id())
                            <form action="{{ route('impersonate.start', $user) }}" method="POST">
                                @csrf
                                <button class="text-violet-600 hover:text-violet-800 font-medium">Login as</button>
                            </form>
                            @endif
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

    {{-- ── Tablet-only Operators ── --}}
    @if($tabletUsers->isNotEmpty())
    <div class="mt-8">
        <h2 class="text-lg font-semibold text-slate-700 mb-3">Tablet Operators</h2>
        <p class="text-slate-400 text-sm mb-4">These accounts can only sign in via PIN on the factory tablet — no web portal access.</p>
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
            <table class="w-full text-sm" style="min-width:400px;">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Name</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Role</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($tabletUsers as $user)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-5 py-4 font-medium text-slate-800">{{ $user->name }}</td>
                        <td class="px-5 py-4">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs" style="background:#ecfdf5;color:#059669;font-weight:600;">Tablet</span>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                {{ $user->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex items-center justify-end gap-3">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-sky-600 hover:text-sky-800 font-medium">Edit</a>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                    onsubmit="return confirm('Remove {{ $user->name }}?')">
                                    @csrf @method('DELETE')
                                    <button class="text-red-500 hover:text-red-700 font-medium">Remove</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
        </div>
    </div>
    @endif
</main>
</x-layout>
