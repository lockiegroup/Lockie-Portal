<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;">
<div style="max-width:560px;margin:2rem auto;background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">

    <div style="background:#0f172a;padding:1.5rem 2rem;">
        <p style="margin:0;color:#94a3b8;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.08em;">Lockie Group</p>
        <h1 style="margin:0.25rem 0 0;color:#fff;font-size:1.125rem;font-weight:700;">Clocking Alert — Hasn't Clocked Out</h1>
    </div>

    <div style="padding:1.5rem 2rem;">
        <p style="margin:0 0 1.25rem;font-size:0.9375rem;color:#1e293b;">
            <strong>{{ $employee->name }}</strong> clocked in at <strong>{{ $clockedInAt }}</strong> but has not clocked out.
            Their scheduled finish time was <strong>{{ $scheduledEnd }}</strong>.
        </p>

        <table style="width:100%;border-collapse:collapse;font-size:0.875rem;margin-bottom:1.5rem;">
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:0.5rem 0;color:#64748b;width:40%;">Employee</td>
                <td style="padding:0.5rem 0;color:#1e293b;font-weight:600;">{{ $employee->name }}</td>
            </tr>
            @if($employee->category)
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:0.5rem 0;color:#64748b;">Category</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $employee->category->name }}</td>
            </tr>
            @endif
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:0.5rem 0;color:#64748b;">Date</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $date }}</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:0.5rem 0;color:#64748b;">Clocked in at</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $clockedInAt }}</td>
            </tr>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:0.5rem 0;color:#64748b;">Scheduled finish</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $scheduledEnd }}</td>
            </tr>
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;">Alert sent at</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $currentTime }}</td>
            </tr>
        </table>

        <a href="{{ url('/clocking/timesheets') }}"
           style="display:inline-block;background:#6366f1;color:#fff;text-decoration:none;padding:0.625rem 1.25rem;border-radius:8px;font-size:0.875rem;font-weight:600;">
            View Timesheets →
        </a>
    </div>

    <div style="padding:1rem 2rem;background:#f8fafc;border-top:1px solid #e2e8f0;">
        <p style="margin:0;font-size:0.75rem;color:#94a3b8;">
            This alert was sent automatically by the Lockie Clocking system.
        </p>
    </div>

</div>
</body>
</html>
