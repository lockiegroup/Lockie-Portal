<x-layout title="Activity Log — Lockie Portal">

    <main class="max-w-5xl mx-auto px-6 py-10">

        <div style="margin-bottom:1.75rem;">
            <h1 style="font-size:1.5rem;font-weight:700;color:#0f172a;margin:0 0 0.25rem;">Activity Log</h1>
            <p style="font-size:0.875rem;color:#64748b;margin:0;">All recorded actions across the portal.</p>
        </div>

        {{-- Filters --}}
        <form method="GET" style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-bottom:1.5rem;align-items:flex-end;">
            <div>
                <label style="display:block;font-size:0.72rem;font-weight:600;color:#64748b;margin-bottom:4px;">Staff Member</label>
                <select name="user_id"
                    style="padding:0.45rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;background:#fff;color:#0f172a;outline:none;min-width:160px;"
                    onchange="this.form.submit()">
                    <option value="">All staff</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $userId == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:0.72rem;font-weight:600;color:#64748b;margin-bottom:4px;">Category</label>
                <select name="category"
                    style="padding:0.45rem 0.75rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.875rem;background:#fff;color:#0f172a;outline:none;min-width:160px;"
                    onchange="this.form.submit()">
                    <option value="">All categories</option>
                    @foreach($categories as $key => $label)
                    <option value="{{ $key }}" {{ $category === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @if($userId || $category)
            <a href="{{ route('admin.activity-log') }}"
                style="padding:0.45rem 0.9rem;border:1px solid #e2e8f0;border-radius:8px;font-size:0.8rem;color:#64748b;text-decoration:none;background:#fff;"
                onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='#fff'">
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

        <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;">
            <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;">
            <table style="width:100%;border-collapse:collapse;font-size:0.875rem;min-width:560px;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                        <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#374151;white-space:nowrap;">When</th>
                        <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#374151;">Staff Member</th>
                        <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#374151;">Action</th>
                        <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#374151;">Detail</th>
                        <th style="padding:0.75rem 1rem;text-align:left;font-weight:700;color:#374151;white-space:nowrap;">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    @php
                        [$label, $bg, $color] = $actionLabels[$log->action] ?? [ucfirst(str_replace(['.','_'], ' ', $log->action)), '#f1f5f9', '#374151'];
                    @endphp
                    <tr style="border-bottom:1px solid #f1f5f9;" onmouseover="this.style.background='#fafafa'" onmouseout="this.style.background=''">
                        <td style="padding:0.7rem 1rem;white-space:nowrap;color:#64748b;font-size:0.78rem;">
                            <span title="{{ $log->created_at->format('d M Y H:i:s') }}">
                                {{ $log->created_at->format('d M Y') }}<br>
                                <span style="color:#94a3b8;">{{ $log->created_at->format('H:i') }}</span>
                            </span>
                        </td>
                        <td style="padding:0.7rem 1rem;font-weight:500;color:#0f172a;white-space:nowrap;">
                            {{ $log->user?->name ?? '—' }}
                        </td>
                        <td style="padding:0.7rem 1rem;white-space:nowrap;">
                            <span style="display:inline-block;padding:2px 8px;border-radius:9999px;font-size:0.72rem;font-weight:600;background:{{ $bg }};color:{{ $color }};">
                                {{ $label }}
                            </span>
                        </td>
                        <td style="padding:0.7rem 1rem;color:#374151;font-size:0.82rem;">{{ $log->description }}</td>
                        <td style="padding:0.7rem 1rem;color:#94a3b8;font-size:0.78rem;white-space:nowrap;">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding:3rem;text-align:center;color:#94a3b8;font-size:0.875rem;">No activity recorded yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>

        {{-- Pagination --}}
        @if($logs->hasPages())
        <div style="margin-top:1rem;display:flex;justify-content:center;">
            {{ $logs->links() }}
        </div>
        @endif

    </main>

</x-layout>
