<!doctype html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f8fafc;margin:0;padding:2rem;">
<div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
    <div style="background:#1e293b;padding:1.25rem 1.5rem;">
        <p style="color:#94a3b8;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 0.25rem;">Clocking Alert</p>
        <h1 style="color:#f1f5f9;font-size:1.25rem;font-weight:700;margin:0;">Hasn't clocked in</h1>
    </div>
    <div style="padding:1.5rem;">
        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;width:140px;">Employee</td>
                <td style="padding:0.5rem 0;font-weight:700;color:#1e293b;">{{ $employee->name }}</td>
            </tr>
            @if($employee->category)
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;">Category</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $employee->category->name }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;">Date</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $date }}</td>
            </tr>
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;">Scheduled start</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $scheduledStart }}</td>
            </tr>
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;">Alert sent at</td>
                <td style="padding:0.5rem 0;color:#ef4444;font-weight:600;">{{ $currentTime }}</td>
            </tr>
        </table>
        <div style="margin-top:1.5rem;">
            <a href="{{ url('/clocking/timesheets') }}"
               style="display:inline-block;padding:0.6rem 1.25rem;background:#6366f1;color:#fff;border-radius:8px;text-decoration:none;font-size:0.875rem;font-weight:600;">
                View Timesheets →
            </a>
        </div>
    </div>
    <div style="padding:1rem 1.5rem;border-top:1px solid #f1f5f9;font-size:0.75rem;color:#94a3b8;">
        This alert was sent by Lockie Portal. To stop receiving these alerts, update your settings at
        <a href="{{ url('/clocking/settings') }}" style="color:#6366f1;">Clocking Settings</a>.
    </div>
</div>
</body>
</html>
