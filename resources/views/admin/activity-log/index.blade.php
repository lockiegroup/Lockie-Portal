<x-layout title="Activity Log — Lockie Portal">

    <main class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

        <div class="mb-7">
            <h1 class="text-2xl font-bold text-slate-800 mb-1">Activity Log</h1>
            <p class="text-sm text-slate-500">All recorded actions across the portal.</p>
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap gap-3 mb-6 items-end">
            <div>
                <label class="block text-[0.72rem] font-semibold text-slate-500 mb-1">Staff Member</label>
                <select name="user_id"
                    class="px-3 py-[0.45rem] border border-slate-200 rounded-lg text-sm bg-white text-slate-800 outline-none min-w-[160px] focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                    onchange="this.form.submit()">
                    <option value="">All staff</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[0.72rem] font-semibold text-slate-500 mb-1">Category</label>
                <select name="category"
                    class="px-3 py-[0.45rem] border border-slate-200 rounded-lg text-sm bg-white text-slate-800 outline-none min-w-[160px] focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                    onchange="this.form.submit()">
                    <option value="">All categories</option>
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($userId || $category)
            <a href="{{ route('admin.activity-log') }}"
                class="px-3.5 py-[0.45rem] border border-slate-200 rounded-lg text-sm text-slate-500 bg-white no-underline hover:bg-slate-50 transition-colors">
                Clear
            </a>
            @endif
        </form>

        @php
            $actionLabels = [
                'auth.login'          => ['Login',                  '#dcfce7', '#16a34a'],
                'auth.logout'         => ['Logout',                 '#f1f5f9', '#475569'],
                'envelope.generate'   => ['Envelope Generated',     '#eff6ff', '#1d4ed8'],
                'print.sync'          => ['Print Sync',             '#faf5ff', '#7c3aed'],
                'print.board_move'    => ['Board Move',             '#faf5ff', '#7c3aed'],
                'print.complete'      => ['Job Completion',         '#faf5ff', '#7c3aed'],
                'print.note_add'      => ['Note Added',             '#faf5ff', '#7c3aed'],
                'print.note_delete'   => ['Note Deleted',           '#fff7ed', '#c2410c'],
                'print.manual_add'    => ['Manual Job Added',       '#f0fdf4', '#15803d'],
                'print.manual_complete' => ['Manual Job Completed', '#dcfce7', '#15803d'],
                'print.manual_archive'  => ['Manual Job Archived',  '#f1f5f9', '#475569'],
                'policy.download'     => ['Policy Download',        '#eff6ff', '#1d4ed8'],
                'policy.upload'       => ['Policy Upload',          '#f0fdf4', '#15803d'],
                'policy.update'       => ['Policy Updated',         '#fefce8', '#a16207'],
                'policy.delete'       => ['Policy Deleted',         '#fef2f2', '#dc2626'],
                'cashflow.entry_add'  => ['Cash Flow Entry',        '#f0fdf4', '#15803d'],
                'cashflow.entry_delete' => ['CF Entry Deleted',     '#fef2f2', '#dc2626'],
                'users.create'        => ['User Created',           '#f0fdf4', '#15803d'],
                'users.update'        => ['User Updated',           '#fefce8', '#a16207'],
                'users.delete'        => ['User Deleted',           '#fef2f2', '#dc2626'],
            ];
        @endphp

        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
            <table class="w-full text-sm border-collapse" style="min-width:560px;">
                <thead>
                    <tr class="bg-slate-50 border-b-2 border-slate-200">
                        <th class="text-left px-4 py-3 font-bold text-slate-700 whitespace-nowrap">When</th>
                        <th class="text-left px-4 py-3 font-bold text-slate-700">Staff Member</th>
                        <th class="text-left px-4 py-3 font-bold text-slate-700">Action</th>
                        <th class="text-left px-4 py-3 font-bold text-slate-700">Detail</th>
                        <th class="text-left px-4 py-3 font-bold text-slate-700 whitespace-nowrap">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    @php
                        [$label, $bg, $color] = $actionLabels[$log->action] ?? [ucfirst(str_replace(['.','_'], ' ', $log->action)), '#f1f5f9', '#374151'];
                    @endphp
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="px-4 py-2.5 whitespace-nowrap text-slate-500 text-[0.78rem]">
                            <span title="{{ $log->created_at->format('d M Y H:i:s') }}">
                                {{ $log->created_at->format('d M Y') }}<br>
                                <span class="text-slate-400">{{ $log->created_at->format('H:i') }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-2.5 font-medium text-slate-800 whitespace-nowrap">
                            {{ $log->user?->name ?? '—' }}
                        </td>
                        <td class="px-4 py-2.5 whitespace-nowrap">
                            <span class="inline-block px-2 py-0.5 rounded-full text-[0.72rem] font-semibold" style="background:{{ $bg }};color:{{ $color }};">
                                {{ $label }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-slate-700 text-[0.82rem]">{{ $log->description }}</td>
                        <td class="px-4 py-2.5 text-slate-400 text-[0.78rem] whitespace-nowrap">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-4 py-12 text-center text-slate-400 text-sm">No activity recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div class="mt-4 flex justify-center">
            {{ $logs->links() }}
        </div>
        @endif

    </main>

</x-layout>
