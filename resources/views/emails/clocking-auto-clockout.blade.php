<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<div style="max-width:560px;margin:2rem auto;background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">

    <div style="background:#0f172a;padding:1.5rem 2rem;">
        <p style="margin:0;color:#94a3b8;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em;">Lockie Group</p>
        <h1 style="margin:0.25rem 0 0;color:#fff;font-size:1.125rem;font-weight:700;">Auto Clock-Out — Action Required</h1>
    </div>

    <div style="padding:1.5rem 2rem;">
        <p style="margin:0 0 1.25rem;font-size:0.9375rem;color:#1e293b;">
            The following
            {{ count($employees) === 1 ? 'employee' : count($employees) . ' employees' }}
            @if($category) in <strong>{{ $category->name }}</strong>@endif
            did not clock out and {{ count($employees) === 1 ? 'was' : 'were' }} automatically clocked out at
            <strong>23:59</strong>. Please review and correct their clock-out time in the portal.
        </p>

        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;margin-bottom:1.5rem;">
            <thead>
                <tr style="background:#f8fafc;border-bottom:2px solid #e2e8f0;">
                    <th style="padding:0.5rem 0.75rem;text-align:left;color:#64748b;font-weight:600;">Employee</th>
                    <th style="padding:0.5rem 0.75rem;text-align:left;color:#64748b;font-weight:600;">Date</th>
                    <th style="padding:0.5rem 0.75rem;text-align:left;color:#64748b;font-weight:600;">Clocked In</th>
                </tr>
            </thead>
            <tbody>
                @foreach($employees as $emp)
                <tr style="border-bottom:1px solid #f1f5f9;">
                    <td style="padding:0.5rem 0.75rem;color:#1e293b;font-weight:600;">{{ $emp['name'] }}</td>
                    <td style="padding:0.5rem 0.75rem;color:#475569;">{{ $emp['date'] }}</td>
                    <td style="padding:0.5rem 0.75rem;color:#475569;">{{ $emp['clocked_in_at'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <a href="{{ url('/clocking/reports') }}"
           style="display:inline-block;background:#6366f1;color:#fff;text-decoration:none;padding:0.625rem 1.25rem;border-radius:8px;font-size:0.875rem;font-weight:600;">
            View Clocking Reports →
        </a>
    </div>

    <div style="padding:1rem 2rem;background:#fef9c3;border-top:1px solid #fde68a;">
        <p style="margin:0;font-size:0.75rem;color:#92400e;">
            ⚠️ These clock-out times are <strong>not accurate</strong> — they were set automatically at midnight because the employee did not clock out. Please correct them in the portal.
        </p>
    </div>

    <div style="padding:1rem 2rem;background:#f8fafc;border-top:1px solid #e2e8f0;">
        <p style="margin:0;font-size:0.75rem;color:#94a3b8;">
            This alert was sent automatically by the Lockie Clocking system.
        </p>
    </div>

</div>
</body>
</html>
