<x-layout title="Key Accounts Admin — Lockie Portal">
<main class="max-w-4xl mx-auto px-6 py-10">

    <div class="mb-8 flex items-center justify-between flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Key Accounts</h1>
            <p class="text-slate-500 mt-1">Manage accounts and salesperson assignments.</p>
        </div>
        <a href="{{ route('key-accounts.index') }}"
           class="text-sm text-slate-500 hover:text-slate-700 transition">← View Dashboard</a>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="mb-6 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3">{{ $errors->first() }}</div>
    @endif

    {{-- Add new account --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-8">
        <h2 class="font-semibold text-slate-800 mb-4">Add Account</h2>
        <form action="{{ route('admin.key-accounts.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-2 gap-4 mb-4 sm:grid-cols-4">
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Account Code <span class="text-red-500">*</span></label>
                    <input type="text" name="account_code" value="{{ old('account_code') }}" required placeholder="e.g. BESGROUP"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-xs font-medium text-slate-600 mb-1">Name <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. BES Group Ltd"
                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Type <span class="text-red-500">*</span></label>
                    <select name="type" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="key" {{ old('type') === 'key' ? 'selected' : '' }}>Key Account</option>
                        <option value="growth" {{ old('type') === 'growth' ? 'selected' : '' }}>Growth Account</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-600 mb-1">Assigned To</label>
                    <select name="user_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                        <option value="">— Unassigned —</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition">
                Add Account
            </button>
        </form>
    </div>

    {{-- Accounts list --}}
    <div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
        @if($accounts->isEmpty())
        <div class="p-12 text-center text-slate-400 text-sm">No accounts yet. Add one above.</div>
        @else
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-slate-50">
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Code</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Name</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Type</th>
                    <th class="text-left px-4 py-3 font-semibold text-slate-600">Assigned To</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($accounts as $account)
                <tr id="row-{{ $account->id }}" class="hover:bg-slate-50">
                    <td class="px-4 py-3 font-semibold text-slate-800">{{ $account->account_code }}</td>
                    <td class="px-4 py-3 text-slate-700">{{ $account->name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $account->type === 'key' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }}">
                            {{ $account->type === 'key' ? 'Key' : 'Growth' }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $account->user?->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-right">
                        <button onclick="openEdit({{ $account->id }})"
                            class="text-xs text-sky-600 hover:text-sky-800 font-medium mr-3">Edit</button>
                        <form action="{{ route('admin.key-accounts.destroy', $account) }}" method="POST" class="inline"
                            onsubmit="return confirm('Delete {{ $account->account_code }}? This will remove all contacts and gifts for this account.')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">Delete</button>
                        </form>
                    </td>
                </tr>
                {{-- Inline edit row (hidden) --}}
                <tr id="edit-{{ $account->id }}" class="hidden bg-sky-50 border-b border-sky-100">
                    <td colspan="5" class="px-4 py-4">
                        <form action="{{ route('admin.key-accounts.update', $account) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 mb-3">
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Account Code</label>
                                    <input type="text" name="account_code" value="{{ $account->account_code }}" required
                                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Name</label>
                                    <input type="text" name="name" value="{{ $account->name }}" required
                                        class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Type</label>
                                    <select name="type" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                                        <option value="key" {{ $account->type === 'key' ? 'selected' : '' }}>Key Account</option>
                                        <option value="growth" {{ $account->type === 'growth' ? 'selected' : '' }}>Growth Account</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-slate-600 mb-1">Assigned To</label>
                                    <select name="user_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                                        <option value="">— Unassigned —</option>
                                        @foreach($users as $user)
                                        <option value="{{ $user->id }}" {{ $account->user_id == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button type="submit" class="px-3 py-1.5 bg-slate-900 text-white text-xs font-semibold rounded-lg hover:bg-slate-700 transition">Save</button>
                                <button type="button" onclick="closeEdit({{ $account->id }})"
                                    class="px-3 py-1.5 border border-slate-300 text-xs font-medium rounded-lg hover:bg-slate-50 transition">Cancel</button>
                            </div>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

</main>

<script>
function openEdit(id) {
    document.getElementById('edit-' + id).classList.remove('hidden');
    document.getElementById('row-' + id).classList.add('hidden');
}
function closeEdit(id) {
    document.getElementById('edit-' + id).classList.add('hidden');
    document.getElementById('row-' + id).classList.remove('hidden');
}
</script>
</x-layout>
