<!doctype html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f8fafc;margin:0;padding:2rem;">
<div style="max-width:500px;margin:0 auto;background:#fff;border-radius:12px;border:1px solid #e2e8f0;overflow:hidden;">
    <div style="background:#1e293b;padding:1.25rem 1.5rem;">
        <p style="color:#94a3b8;font-size:0.75rem;text-transform:uppercase;letter-spacing:0.1em;margin:0 0 0.25rem;">CRM Reminder</p>
        <h1 style="color:#f1f5f9;font-size:1.25rem;font-weight:700;margin:0;">Follow-up Due Today</h1>
    </div>
    <div style="padding:1.5rem;">
        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;width:140px;">Account</td>
                <td style="padding:0.5rem 0;font-weight:700;color:#1e293b;">
                    {{ $contact->account?->account_code }}
                    @if($contact->account?->name)
                        <span style="font-weight:400;color:#64748b;"> — {{ $contact->account->name }}</span>
                    @endif
                </td>
            </tr>
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;">Last contacted</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $contact->contacted_at->format('d M Y') }}</td>
            </tr>
            @if($contact->user)
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;">Logged by</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $contact->user->name }}</td>
            </tr>
            @endif
            <tr>
                <td style="padding:0.5rem 0;color:#64748b;vertical-align:top;">Note</td>
                <td style="padding:0.5rem 0;color:#1e293b;">{{ $contact->note }}</td>
            </tr>
        </table>
        <div style="margin-top:1.5rem;">
            <a href="{{ config('app.url') }}/crm/{{ $contact->account?->account_code }}"
               style="display:inline-block;background:#1e293b;color:#fff;text-decoration:none;padding:0.6rem 1.25rem;border-radius:8px;font-size:0.85rem;font-weight:600;">
                View Account →
            </a>
        </div>
    </div>
    <div style="padding:1rem 1.5rem;background:#f8fafc;border-top:1px solid #e2e8f0;">
        <p style="margin:0;font-size:0.78rem;color:#94a3b8;">
            This reminder was sent by Lockie Portal. You set a follow-up for {{ $contact->follow_up_at->format('d M Y') }}.
        </p>
    </div>
</div>
</body>
</html>
