<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>H&S Action Reminder</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; color: #1e293b; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background: #0f172a; padding: 24px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 18px; font-weight: 600; }
        .header p { color: #94a3b8; margin: 4px 0 0; font-size: 14px; }
        .body { padding: 32px; }
        .section-title { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 12px; }
        .section-title.overdue { color: #dc2626; }
        .section-title.due-soon { color: #0369a1; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 28px; font-size: 14px; }
        th { text-align: left; padding: 8px 12px; background: #f8fafc; color: #64748b; font-weight: 600; font-size: 12px; border-bottom: 1px solid #e2e8f0; }
        td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
        tr:last-child td { border-bottom: none; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 9999px; font-size: 11px; font-weight: 600; }
        .badge-critical { background: #fee2e2; color: #991b1b; }
        .badge-high     { background: #ffedd5; color: #9a3412; }
        .badge-medium   { background: #fef9c3; color: #854d0e; }
        .badge-low      { background: #f0fdf4; color: #166534; }
        .cta { text-align: center; margin-top: 8px; }
        .btn { display: inline-block; background: #0f172a; color: #fff; text-decoration: none; padding: 12px 28px; border-radius: 8px; font-weight: 600; font-size: 14px; }
        .footer { padding: 20px 32px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Health &amp; Safety — Action Reminder</h1>
        <p>{{ now()->format('l, j F Y') }}</p>
    </div>
    <div class="body">

        @if($overdue->isNotEmpty())
            <p class="section-title overdue">⚠ Overdue ({{ $overdue->count() }})</p>
            <table>
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Due</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($overdue as $action)
                        <tr>
                            <td>{{ $action->title }}</td>
                            <td style="color:#dc2626;white-space:nowrap;">{{ $action->due_date->format('d M Y') }}</td>
                            <td><span class="badge badge-{{ $action->priority }}">{{ ucfirst($action->priority) }}</span></td>
                            <td>{{ $action->assignedUser?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($dueSoon->isNotEmpty())
            <p class="section-title due-soon">Upcoming — due within {{ $daysBefore }} days ({{ $dueSoon->count() }})</p>
            <table>
                <thead>
                    <tr>
                        <th>Action</th>
                        <th>Due</th>
                        <th>Priority</th>
                        <th>Assigned To</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($dueSoon as $action)
                        <tr>
                            <td>{{ $action->title }}</td>
                            <td style="white-space:nowrap;">{{ $action->due_date->format('d M Y') }}</td>
                            <td><span class="badge badge-{{ $action->priority }}">{{ ucfirst($action->priority) }}</span></td>
                            <td>{{ $action->assignedUser?->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <div class="cta">
            <a href="{{ url('/health-safety/actions') }}" class="btn">View All Actions →</a>
        </div>
    </div>
    <div class="footer">
        Lockie Portal &mdash; Health &amp; Safety Module<br>
        You received this because you are listed as a H&amp;S notification recipient.
    </div>
</div>
</body>
</html>
