<x-layout title="{{ $keyAccount->account_code }} — Key Accounts — Lockie Portal">
<main class="max-w-3xl mx-auto px-6 py-10">

    <div class="mb-6">
        <a href="{{ route('key-accounts.index') }}" class="text-sm text-slate-500 hover:text-slate-700 transition">← Key Accounts</a>
    </div>

    <div class="flex items-start justify-between gap-4 mb-8 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">{{ $keyAccount->account_code }}</h1>
            <p class="text-slate-500 mt-0.5">{{ $keyAccount->name }}</p>
            <div class="flex items-center gap-3 mt-2">
                <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold {{ $keyAccount->type === 'key' ? 'bg-sky-100 text-sky-700' : 'bg-emerald-100 text-emerald-700' }}">
                    {{ $keyAccount->type === 'key' ? 'Key Account' : 'Growth Account' }}
                </span>
                @if($keyAccount->user)
                <span class="text-sm text-slate-500">Assigned to <strong class="text-slate-700">{{ $keyAccount->user->name }}</strong></span>
                @endif
            </div>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3">{{ session('success') }}</div>
    @endif

    {{-- Notes --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="font-semibold text-slate-800 mb-3">Notes</h2>
        <form action="{{ route('key-accounts.notes.update', $keyAccount) }}" method="POST">
            @csrf @method('PATCH')
            <textarea name="notes" rows="3"
                placeholder="Any notes about this account…"
                class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-slate-900 placeholder-slate-400 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 resize-none">{{ old('notes', $keyAccount->notes) }}</textarea>
            <div class="mt-2 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition">Save Notes</button>
            </div>
        </form>
    </div>

    {{-- Log contact --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="font-semibold text-slate-800 mb-4">Log Contact</h2>
        <form action="{{ route('key-accounts.contacts.store', $keyAccount) }}" method="POST" class="space-y-3">
            @csrf
            @if($errors->has('contacted_at') || $errors->has('note'))
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-2">{{ $errors->first() }}</div>
            @endif
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Date</label>
                    <input type="date" name="contacted_at" value="{{ old('contacted_at', now()->format('Y-m-d')) }}" required
                        class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-sky-500">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Note</label>
                <textarea name="note" rows="3" required placeholder="What was discussed or actioned…"
                    class="w-full px-4 py-2.5 rounded-lg border border-slate-300 text-sm placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-sky-500 resize-none">{{ old('note') }}</textarea>
            </div>
            <div class="flex justify-end">
                <button type="submit" class="px-4 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition">Log Contact</button>
            </div>
        </form>
    </div>

    {{-- Contact log --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6 mb-6">
        <h2 class="font-semibold text-slate-800 mb-4">Contact History</h2>
        @if($keyAccount->contacts->isEmpty())
            <p class="text-sm text-slate-400">No contacts logged yet.</p>
        @else
        <div class="space-y-3">
            @foreach($keyAccount->contacts as $contact)
            <div class="flex gap-4 items-start pb-3 border-b border-slate-100 last:border-0 last:pb-0">
                <div class="text-xs text-slate-400 whitespace-nowrap pt-0.5 w-24 flex-shrink-0">
                    {{ $contact->contacted_at->format('d M Y') }}
                </div>
                <div class="flex-1 text-sm text-slate-700">{{ $contact->note }}</div>
                <div class="text-xs text-slate-400 whitespace-nowrap flex-shrink-0">{{ $contact->user?->name ?? '—' }}</div>
                <form action="{{ route('key-accounts.contacts.destroy', [$keyAccount, $contact]) }}" method="POST"
                    onsubmit="return confirm('Remove this contact entry?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-slate-300 hover:text-red-500 transition text-lg leading-none">&times;</button>
                </form>
            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Gift history --}}
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="font-semibold text-slate-800 mb-4">Gift History</h2>
        @if($keyAccount->gifts->isEmpty())
            <p class="text-sm text-slate-400">No gifts recorded yet. Use Import Gifts on the dashboard to add them.</p>
        @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="text-left pb-2 text-slate-500 font-semibold text-xs uppercase tracking-wide">Date</th>
                        <th class="text-left pb-2 text-slate-500 font-semibold text-xs uppercase tracking-wide pl-4">Recipient</th>
                        <th class="text-left pb-2 text-slate-500 font-semibold text-xs uppercase tracking-wide pl-4">Gift</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($keyAccount->gifts as $gift)
                    <tr>
                        <td class="py-2.5 text-slate-500 whitespace-nowrap">{{ $gift->gifted_at->format('d M Y') }}</td>
                        <td class="py-2.5 pl-4 text-slate-700">{{ $gift->recipient }}</td>
                        <td class="py-2.5 pl-4 text-slate-700">{{ $gift->description }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

</main>
</x-layout>
