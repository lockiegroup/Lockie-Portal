<!doctype html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f8fafc;margin:0;padding:2rem;">
<div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
    <div style="background:#7f1d1d;padding:1.25rem 1.5rem;">
        <p style="color:#fca5a5;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 0.25rem;">Import Alert</p>
        <h1 style="color:#fff1f2;font-size:1.25rem;font-weight:700;margin:0;">Import Failed</h1>
    </div>
    <div style="padding:1.5rem;">
        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;width:140px;">Import type</td>
                <td style="padding:0.5rem 0;font-weight:700;color:#1e293b;">{{ $importType }}</td>
            </tr>
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;">Failed at</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $occurredAt }}</td>
            </tr>
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;vertical-align:top;">Error</td>
                <td style="padding:0.5rem 0;color:#dc2626;font-family:monospace;font-size:0.82rem;word-break:break-all;">{{ $errorMessage }}</td>
            </tr>
        </table>
        <div style="margin-top:1.5rem;">
            <a href="{{ config('app.url') }}/admin/activity-log?category=imports"
               style="display:inline-block;background:#1e293b;color:#fff;text-decoration:none;padding:0.6rem 1.25rem;border-radius:8px;font-size:0.85rem;font-weight:600;">
                View Activity Log →
            </a>
        </div>
    </div>
    <div style="padding:1rem 1.5rem;background:#f8fafc;border-top:1px solid #e2e8f0;">
        <p style="margin:0;font-size:0.78rem;color:#94a3b8;">
            This alert was sent by Lockie Portal. To update alert recipients, set <code>IMPORT_ALERT_EMAIL</code> in your environment config.
        </p>
    </div>
</div>
</body>
</html>
