<x-layout title="Manage Users — Lockie Portal">

    <main class="max-w-5xl mx-auto px-6 py-10">
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
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Name</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Email</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Role</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Status</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Last Login</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $user)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-medium text-slate-800">{{ $user->name }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $user->email }}</td>
                            <td class="px-6 py-4">
                                @if($user->role === 'master')
                                    <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:0.75rem;font-weight:600;background:#f3e8ff;color:#7c3aed;">Master</span>
                                @elseif($user->role === 'admin')
                                    <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:0.75rem;font-weight:600;background:#e0f2fe;color:#0369a1;">Admin</span>
                                @else
                                    <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:0.75rem;font-weight:600;background:#f1f5f9;color:#475569;">Staff</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 text-sm">
                                @if($user->last_login_at)
                                    <span title="{{ $user->last_login_at->format('d M Y H:i') }}">
                                        {{ $user->last_login_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-slate-300">Never</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right flex items-center justify-end gap-3">
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-sky-600 hover:text-sky-800 font-medium">Edit</a>
                                @if($user->id !== auth()->id())
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                        onsubmit="return confirm('Remove {{ $user->name }}?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:text-red-700 font-medium">Remove</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </main>
</x-layout>
