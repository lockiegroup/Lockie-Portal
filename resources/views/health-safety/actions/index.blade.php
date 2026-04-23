<x-layout title="H&S Actions — Lockie Portal">

    <main class="max-w-5xl mx-auto px-6 py-10">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Action Tracker</h1>
                <p class="text-slate-500 text-sm mt-1">Track, assign and resolve health &amp; safety actions.</p>
            </div>
            <a href="{{ route('hs.actions.create') }}"
                class="bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold px-4 py-2.5 rounded-lg transition-colors">
                + New Action
            </a>
        </div>

        {{-- Status counts --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
            <a href="{{ route('hs.actions.index', ['status' => 'overdue']) }}"
                class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:border-red-300 transition-colors">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Overdue</p>
                <p class="text-2xl font-bold text-red-600 mt-1">{{ $counts['overdue'] }}</p>
            </a>
            <a href="{{ route('hs.actions.index', ['status' => 'open']) }}"
                class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:border-amber-300 transition-colors">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Open</p>
                <p class="text-2xl font-bold text-amber-600 mt-1">{{ $counts['open'] }}</p>
            </a>
            <a href="{{ route('hs.actions.index', ['status' => 'in_progress']) }}"
                class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:border-sky-300 transition-colors">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">In Progress</p>
                <p class="text-2xl font-bold text-sky-600 mt-1">{{ $counts['in_progress'] }}</p>
            </a>
            <a href="{{ route('hs.actions.index') }}"
                class="bg-white rounded-xl border border-slate-200 shadow-sm p-4 hover:border-amber-300 transition-colors">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wide">Due in 7 days</p>
                <p class="text-2xl font-bold text-amber-500 mt-1">{{ $counts['due_soon'] }}</p>
            </a>
        </div>

        @if(session('success'))
            <div class="mb-5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg px-4 py-3">
                {{ session('success') }}
            </div>
        @endif

        {{-- Filter tabs --}}
        <div class="flex gap-2 mb-4 flex-wrap">
            @foreach(['all' => 'All', 'open' => 'Open', 'in_progress' => 'In Progress', 'overdue' => 'Overdue', 'completed' => 'Completed'] as $val => $label)
                <a href="{{ route('hs.actions.index', ['status' => $val]) }}"
                    class="px-3 py-1.5 text-sm font-medium rounded-lg border transition-colors
                        {{ (request('status', 'all') === $val) ? 'bg-slate-900 text-white border-slate-900' : 'border-slate-200 text-slate-600 hover:bg-slate-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-slate-50">
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Action</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600 hidden sm:table-cell">Assigned To</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Due</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600 hidden md:table-cell">Priority</th>
                        <th class="text-left px-6 py-3 font-semibold text-slate-600">Status</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($actions as $action)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <p class="font-medium text-slate-800">{{ $action->title }}</p>
                                @if($action->is_recurring)
                                    <p class="text-xs text-sky-600 mt-0.5">↻ Repeats {{ $action->recurrence_type }}</p>
                                @endif
                                @if($action->location)
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $action->location }}</p>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600 hidden sm:table-cell">
                                {{ $action->assignedUser?->name ?? '—' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600 whitespace-nowrap">
                                <span class="{{ $action->status === 'overdue' ? 'text-red-600 font-medium' : '' }}">
                                    {{ $action->due_date->format('d M Y') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 hidden md:table-cell">
                                @php
                                    $priorityCls = match($action->priority) {
                                        'critical' => 'bg-red-100 text-red-700',
                                        'high'     => 'bg-orange-100 text-orange-700',
                                        'medium'   => 'bg-amber-100 text-amber-700',
                                        default    => 'bg-slate-100 text-slate-600',
                                    };
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $priorityCls }}">
                                    {{ ucfirst($action->priority) }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusCls = match($action->status) {
                                        'overdue'     => 'bg-red-100 text-red-700',
                                        'open'        => 'bg-amber-100 text-amber-700',
                                        'in_progress' => 'bg-sky-100 text-sky-700',
                                        'completed'   => 'bg-emerald-100 text-emerald-700',
                                        default       => 'bg-slate-100 text-slate-600',
                                    };
                                    $statusLabel = match($action->status) {
                                        'in_progress' => 'In Progress',
                                        default       => ucfirst($action->status),
                                    };
                                @endphp
                                <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium {{ $statusCls }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if($action->status !== 'completed')
                                        <form action="{{ route('hs.actions.complete', $action) }}" method="POST">
                                            @csrf @method('PATCH')
                                            <button class="text-emerald-600 hover:text-emerald-800 font-medium text-xs">Done</button>
                                        </form>
                                    @endif
                                    <a href="{{ route('hs.actions.edit', $action) }}" class="text-sky-600 hover:text-sky-800 font-medium text-xs">Edit</a>
                                    <form action="{{ route('hs.actions.destroy', $action) }}" method="POST"
                                        onsubmit="return confirm('Remove this action?')">
                                        @csrf @method('DELETE')
                                        <button class="text-red-500 hover:text-red-700 font-medium text-xs">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-400">No actions found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </main>
</x-layout>
