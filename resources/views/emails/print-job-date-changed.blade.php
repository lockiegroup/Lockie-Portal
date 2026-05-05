<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Date Changed</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f8fafc; margin: 0; padding: 0; color: #1e293b; }
        .wrapper { max-width: 600px; margin: 32px auto; background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
        .header { background: #0f172a; padding: 24px 32px; }
        .header h1 { color: #fff; margin: 0; font-size: 18px; font-weight: 600; }
        .header p { color: #94a3b8; margin: 4px 0 0; font-size: 14px; }
        .body { padding: 32px; }
        .highlight { background: #fefce8; border: 1px solid #fde047; border-radius: 8px; padding: 16px 20px; margin-bottom: 28px; }
        .highlight-label { font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #854d0e; margin-bottom: 8px; }
        .date-row { display: flex; gap: 24px; flex-wrap: wrap; }
        .date-block { flex: 1; min-width: 120px; }
        .date-block .label { font-size: 12px; color: #64748b; margin-bottom: 2px; }
        .date-block .value { font-size: 16px; font-weight: 700; color: #1e293b; }
        .date-block .value.new { color: #15803d; }
        .date-block .value.old { color: #dc2626; text-decoration: line-through; }
        .changed-by { margin-top: 10px; font-size: 13px; color: #64748b; }
        .changed-by strong { color: #1e293b; }
        .section-title { font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: #64748b; margin: 0 0 12px; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        td { padding: 9px 0; border-bottom: 1px solid #f1f5f9; color: #334155; vertical-align: top; }
        td:first-child { color: #64748b; width: 40%; padding-right: 12px; }
        tr:last-child td { border-bottom: none; }
        .footer { padding: 20px 32px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Delivery Date Changed</h1>
        <p>{{ now()->format('l, j F Y \a\t g:i a') }}</p>
    </div>
    <div class="body">

        <div class="highlight">
            <div class="highlight-label">Date Update</div>
            <div class="date-row">
                @if($oldDate)
                <div class="date-block">
                    <div class="label">Previous date</div>
                    <div class="value old">{{ \Carbon\Carbon::parse($oldDate)->format('d M Y') }}</div>
                </div>
                @endif
                <div class="date-block">
                    <div class="label">New date</div>
                    <div class="value new">{{ \Carbon\Carbon::parse($newDate)->format('d M Y') }}</div>
                </div>
                @if($job->original_required_date)
                <div class="date-block">
                    <div class="label">Initial date</div>
                    <div class="value">{{ $job->original_required_date->format('d M Y') }}</div>
                </div>
                @endif
            </div>
            <div class="changed-by">Changed by <strong>{{ $changedBy }}</strong></div>
        </div>

        <p class="section-title">Order Details</p>
        <table>
            <tr>
                <td>Order</td>
                <td><strong>{{ $job->order_number }}</strong>@if($job->customer_ref) &nbsp;·&nbsp; Ref: {{ $job->customer_ref }}@endif</td>
            </tr>
            <tr>
                <td>Customer</td>
                <td>{{ $job->customer_name ?: '—' }}</td>
            </tr>
            <tr>
                <td>Product</td>
                <td><strong>{{ $job->product_code }}</strong>@if($job->product_description) — {{ $job->product_description }}@endif</td>
            </tr>
            @if($job->line_comment)
            <tr>
                <td>Print data</td>
                <td>{{ $job->line_comment }}</td>
            </tr>
            @endif
            <tr>
                <td>Quantity</td>
                <td>{{ number_format($job->order_quantity) }} packs</td>
            </tr>
            <tr>
                <td>Order date</td>
                <td>{{ $job->order_date ? $job->order_date->format('d M Y') : '—' }}</td>
            </tr>
            @if($job->order_total)
            <tr>
                <td>Order value</td>
                <td>£{{ number_format($job->order_total, 2) }}</td>
            </tr>
            @endif
        </table>

    </div>
    <div class="footer">
        Lockie Portal &mdash; Print Schedule
    </div>
</div>
</body>
</html>
