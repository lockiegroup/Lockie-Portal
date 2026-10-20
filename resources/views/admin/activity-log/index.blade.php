<x-layout title="Activity Log — Lockie Portal">

    <main class="max-w-6xl mx-auto px-4 sm:px-6 py-8">

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
            <div>
                <label class="block text-[0.72rem] font-semibold text-slate-500 mb-1">From</label>
                <input type="date" name="date_from" value="{{ $dateFrom }}"
                    class="px-3 py-[0.45rem] border border-slate-200 rounded-lg text-sm bg-white text-slate-800 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                    onchange="this.form.submit()">
            </div>
            <div>
                <label class="block text-[0.72rem] font-semibold text-slate-500 mb-1">To</label>
                <input type="date" name="date_to" value="{{ $dateTo }}"
                    class="px-3 py-[0.45rem] border border-slate-200 rounded-lg text-sm bg-white text-slate-800 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                    onchange="this.form.submit()">
            </div>
            <div class="flex-1 min-w-[180px]">
                <label class="block text-[0.72rem] font-semibold text-slate-500 mb-1">Search description</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ $search }}" placeholder="e.g. order number, name…"
                        class="w-full pl-8 pr-3 py-[0.45rem] border border-slate-200 rounded-lg text-sm bg-white text-slate-800 outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-400 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z"/>
                    </svg>
                </div>
            </div>
            <button type="submit"
                class="px-3.5 py-[0.45rem] border border-sky-500 rounded-lg text-sm text-white bg-sky-500 hover:bg-sky-600 transition-colors">
                Search
            </button>
            @if($userId || $category || $search || $dateFrom || $dateTo)
            <a href="{{ route('admin.activity-log') }}"
                class="px-3.5 py-[0.45rem] border border-slate-200 rounded-lg text-sm text-slate-500 bg-white no-underline hover:bg-slate-50 transition-colors">
                Clear
            </a>
            @endif
        </form>

        @php
            $actionLabels = [
                // Auth
                'auth.login'               => ['Login',                    '#dcfce7', '#16a34a'],
                'auth.logout'              => ['Logout',                   '#f1f5f9', '#475569'],
                'auth.impersonate_start'   => ['Impersonate Start',        '#fef3c7', '#92400e'],
                'auth.impersonate_stop'    => ['Impersonate Stop',         '#f1f5f9', '#475569'],
                // Clocking
                'clocking.employee_create' => ['Employee Added',           '#f0fdf4', '#15803d'],
                'clocking.employee_update' => ['Employee Updated',         '#fefce8', '#a16207'],
                'clocking.employee_toggle' => ['Employee Toggled',         '#eff6ff', '#1d4ed8'],
                'clocking.employee_delete' => ['Employee Deleted',         '#fef2f2', '#dc2626'],
                // CRM
                'crm.notes_update'         => ['CRM Notes Updated',       '#fefce8', '#a16207'],
                'crm.contact_add'          => ['CRM Contact Logged',       '#f0fdf4', '#15803d'],
                'crm.contact_delete'       => ['CRM Contact Deleted',      '#fef2f2', '#dc2626'],
                'crm.bulk_contact'         => ['CRM Bulk Contact',         '#ecfdf5', '#065f46'],
                // Church Envelopes
                'envelope.generate'        => ['Envelope Generated',       '#eff6ff', '#1d4ed8'],
                'church-envelope.download' => ['Envelope Downloaded',      '#eff6ff', '#1d4ed8'],
                // Print Schedule
                'print.sync'               => ['Print Sync',               '#faf5ff', '#7c3aed'],
                'print.board_move'         => ['Board Move',               '#faf5ff', '#7c3aed'],
                'print.complete'           => ['Job Completion',           '#faf5ff', '#7c3aed'],
                'print.note_add'           => ['Note Added',               '#faf5ff', '#7c3aed'],
                'print.note_delete'        => ['Note Deleted',             '#fff7ed', '#c2410c'],
                'print.manual_add'         => ['Manual Job Added',         '#f0fdf4', '#15803d'],
                'print.manual_complete'    => ['Manual Job Completed',     '#dcfce7', '#15803d'],
                'print.manual_archive'     => ['Manual Job Archived',      '#f1f5f9', '#475569'],
                // Policies
                'policy.download'          => ['Policy Download',          '#eff6ff', '#1d4ed8'],
                'policy.upload'            => ['Policy Upload',            '#f0fdf4', '#15803d'],
                'policy.update'            => ['Policy Updated',           '#fefce8', '#a16207'],
                'policy.delete'            => ['Policy Deleted',           '#fef2f2', '#dc2626'],
                // Cash Flow
                'cashflow.entry_add'       => ['Cash Flow Entry',          '#f0fdf4', '#15803d'],
                'cashflow.entry_delete'    => ['CF Entry Deleted',         '#fef2f2', '#dc2626'],
                // Racking
                'racking.slot_fill'        => ['Slot Filled',              '#f0fdf4', '#15803d'],
                'racking.slot_update'      => ['Slot Updated',             '#fefce8', '#a16207'],
                'racking.slot_clear'       => ['Slot Cleared',             '#f1f5f9', '#475569'],
                'racking.slot_move'        => ['Slot Moved',               '#eff6ff', '#1d4ed8'],
                'racking.move_outside'     => ['Moved to Outside',         '#fff7ed', '#c2410c'],
                'racking.move_to_rack'     => ['Moved to Rack',            '#ecfdf5', '#065f46'],
                // Users
                'users.create'             => ['User Created',             '#f0fdf4', '#15803d'],
                'users.update'             => ['User Updated',             '#fefce8', '#a16207'],
                'users.delete'             => ['User Deleted',             '#fef2f2', '#dc2626'],
                // Key Accounts
                'key_accounts.created'     => ['KA Created',               '#f0fdf4', '#15803d'],
                'key_accounts.updated'     => ['KA Updated',               '#fefce8', '#a16207'],
                'key_accounts.deleted'     => ['KA Deleted',               '#fef2f2', '#dc2626'],
                'key_accounts.contact'     => ['KA Contact Logged',        '#ecfdf5', '#065f46'],
                'key_accounts.gifts_import'=> ['KA Gifts Imported',        '#eff6ff', '#1d4ed8'],
                'key_accounts.gifts_export'=> ['KA Gifts Exported',        '#eff6ff', '#1d4ed8'],
                // Key Actions
                'key_actions.group_create' => ['Group Created',            '#f0fdf4', '#15803d'],
                'key_actions.group_rename' => ['Group Renamed',            '#fefce8', '#a16207'],
                'key_actions.group_delete' => ['Group Deleted',            '#fef2f2', '#dc2626'],
                'key_actions.member_add'   => ['Member Added',             '#f0fdf4', '#15803d'],
                'key_actions.member_remove'=> ['Member Removed',           '#fef2f2', '#dc2626'],
                'key_actions.member_role'  => ['Role Changed',             '#fefce8', '#a16207'],
                'key_actions.task_create'  => ['Task Created',             '#f0fdf4', '#15803d'],
                'key_actions.task_move'    => ['Task Moved',               '#eff6ff', '#1d4ed8'],
                'key_actions.task_delete'  => ['Task Deleted',             '#fef2f2', '#dc2626'],
                // Imports
                'imports.sales'            => ['Sales Import',             '#eff6ff', '#1d4ed8'],
                'imports.sales.error'      => ['Sales Import Error',       '#fef2f2', '#dc2626'],
                'imports.credits'          => ['Credits Import',           '#eff6ff', '#1d4ed8'],
                'imports.credits.error'    => ['Credits Import Error',     '#fef2f2', '#dc2626'],
                // Reminders
                'reminders.import'         => ['Reminders Import',         '#eff6ff', '#1d4ed8'],
                'reminders.import_phones'  => ['Phones Import',            '#eff6ff', '#1d4ed8'],
                'reminders.import_orders'  => ['Orders Import',            '#eff6ff', '#1d4ed8'],
                'reminders.clear'          => ['Reminders Cleared',        '#f1f5f9', '#475569'],
                'reminders.move'           => ['Reminder Moved',           '#fefce8', '#a16207'],
                // Stock
                'stock_forecast.sync'      => ['Forecast Sync',            '#faf5ff', '#7c3aed'],
                // Misc
                'supplier_settings.update' => ['Supplier Settings',        '#fefce8', '#a16207'],
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
                        [$label, $bg, $color] = $actionLabels[$log->action] ?? [ucwords(str_replace(['.','_'], ' ', $log->action)), '#f1f5f9', '#374151'];
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
