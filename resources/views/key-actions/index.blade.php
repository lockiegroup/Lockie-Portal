<x-layout title="Key Actions — Lockie Portal">

<main class="max-w-4xl mx-auto px-4 sm:px-6 py-8">

    <div class="flex items-center justify-between mb-6 flex-wrap gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Key Actions</h1>
            <p class="text-sm text-slate-500 mt-1">Action boards for team meetings.</p>
        </div>
        @if(auth()->user()->isMaster())
        <button onclick="document.getElementById('new-group-modal').style.display='flex'"
                class="px-3.5 py-2 bg-slate-900 text-white text-sm font-semibold rounded-lg hover:bg-slate-700 transition-colors cursor-pointer border-0">
            + New Group
        </button>
        @endif
    </div>

    @if($groups->isEmpty())
    <div class="bg-white rounded-xl border border-slate-200 p-12 text-center text-slate-400 text-sm">
        No action groups yet.
    </div>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($groups as $group)
        <a href="{{ route('key-actions.show', $group) }}"
           class="block bg-white rounded-xl shadow-sm border border-slate-200 p-5 no-underline hover:shadow-md transition-shadow">
            <p class="text-base font-bold text-slate-800 mb-1">{{ $group->name }}</p>
            <p class="text-[0.8125rem] text-slate-500 mb-3">
                {{ $group->tasks_count }} task{{ $group->tasks_count !== 1 ? 's' : '' }}
            </p>
            <div class="flex flex-wrap gap-1">
                @foreach($group->members->take(6) as $member)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.7rem] font-semibold bg-slate-100 text-slate-600">
                    {{ $member->name }}
                </span>
                @endforeach
                @if($group->members->count() > 6)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.7rem] bg-slate-100 text-slate-400">+{{ $group->members->count() - 6 }}</span>
                @endif
            </div>
        </a>
        @endforeach
    </div>
    @endif

</main>

{{-- New Group Modal --}}
@if(auth()->user()->isMaster())
<div id="new-group-modal" style="display:none;" class="fixed inset-0 bg-black/40 z-[1000] items-center justify-center">
    <div class="bg-white rounded-xl p-6 w-full max-w-[420px] mx-4">
        <h2 class="text-base font-bold text-slate-800 mb-4">New Action Group</h2>
        <form method="POST" action="{{ route('key-actions.store') }}">
            @csrf
            <label class="block text-sm font-semibold text-slate-700 mb-1">Group Name</label>
            <input name="name" required autofocus placeholder="e.g. Management Key Actions"
                   class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-sky-500 mb-4">
            <div class="flex gap-2 justify-end">
                <button type="button" onclick="document.getElementById('new-group-modal').style.display='none'"
                        class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-semibold rounded-lg border-0 cursor-pointer hover:bg-slate-200 transition-colors">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-slate-900 text-white text-sm font-semibold rounded-lg border-0 cursor-pointer hover:bg-slate-700 transition-colors">
                    Create
                </button>
            </div>
        </form>
    </div>
</div>
@endif

</x-layout>
