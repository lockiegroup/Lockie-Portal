<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $worksOrder->title }} — Lockie Group</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 11pt; color: #1e293b; background: white; }
        .page { max-width: 900px; margin: 0 auto; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; border-bottom: 2px solid #0f172a; padding-bottom: 16px; }
        .header-left h1 { font-size: 20pt; font-weight: 700; color: #0f172a; }
        .header-left p { color: #64748b; font-size: 10pt; margin-top: 4px; }
        .header-right { text-align: right; font-size: 9pt; color: #64748b; }
        .meta { display: flex; gap: 32px; margin-bottom: 20px; }
        .meta-item label { font-size: 8pt; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #94a3b8; display: block; }
        .meta-item span { font-size: 11pt; font-weight: 600; color: #0f172a; }
        .notes-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px 14px; margin-bottom: 20px; font-size: 10pt; color: #475569; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        thead tr { background: #0f172a; color: white; }
        thead th { padding: 8px 10px; text-align: left; font-size: 9pt; font-weight: 600; }
        thead th.right { text-align: right; }
        tbody tr { border-bottom: 1px solid #e2e8f0; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody td { padding: 8px 10px; font-size: 10pt; }
        .type-badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 8pt; font-weight: 600; }
        .type-cut_blank { background: #fef3c7; color: #92400e; }
        .type-made { background: #dbeafe; color: #1e40af; }
        .qty { font-weight: 700; font-size: 12pt; text-align: right; }
        .note-cell { color: #64748b; font-size: 9pt; }
        .footer { border-top: 1px solid #e2e8f0; padding-top: 12px; display: flex; justify-content: space-between; font-size: 9pt; color: #94a3b8; }
        .signature-area { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 24px; margin-top: 32px; }
        .sig-box { border-top: 1px solid #cbd5e1; padding-top: 8px; }
        .sig-box label { font-size: 8pt; color: #94a3b8; }
        .no-print { margin-bottom: 20px; }
        @media print {
            .no-print { display: none; }
            body { font-size: 10pt; }
            .page { padding: 15px; }
        }
    </style>
</head>
<body>
<div class="page">

    <div class="no-print">
        <button onclick="window.print()"
            style="background:#0f172a;color:white;border:none;padding:8px 20px;border-radius:6px;cursor:pointer;font-size:11pt;font-weight:600;">
            🖨 Print
        </button>
        <a href="{{ route('collars.index') }}"
            style="margin-left:12px;color:#6366f1;font-size:10pt;">← Back to Collar Production</a>
    </div>

    <div class="header">
        <div class="header-left">
            <h1>{{ $worksOrder->title }}</h1>
            <p>Lockie Group — Collar Production Works Order</p>
        </div>
        <div class="header-right">
            <div>Created: {{ $worksOrder->created_at->format('d/m/Y H:i') }}</div>
            @if($worksOrder->created_by)<div>By: {{ $worksOrder->created_by }}</div>@endif
            <div>Order #{{ $worksOrder->id }}</div>
        </div>
    </div>

    <div class="meta">
        <div class="meta-item">
            <label>Period</label>
            <span>{{ $worksOrder->period->format('F Y') }}</span>
        </div>
        <div class="meta-item">
            <label>Total Lines</label>
            <span>{{ $worksOrder->lines->count() }}</span>
        </div>
        <div class="meta-item">
            <label>Cut Blank Lines</label>
            <span>{{ $worksOrder->lines->where('type', 'cut_blank')->count() }}</span>
        </div>
        <div class="meta-item">
            <label>Made Lines</label>
            <span>{{ $worksOrder->lines->where('type', 'made')->count() }}</span>
        </div>
    </div>

    @if($worksOrder->notes)
    <div class="notes-box">{{ $worksOrder->notes }}</div>
    @endif

    @php
        $cutLines  = $worksOrder->lines->where('type', 'cut_blank')->sortBy(fn($l) => $l->product->description);
        $madeLines = $worksOrder->lines->where('type', 'made')->sortBy(fn($l) => $l->product->description);
    @endphp

    @if($cutLines->count())
    <h3 style="font-size:11pt;font-weight:700;color:#92400e;margin-bottom:8px;background:#fef3c7;padding:6px 10px;border-radius:4px;">
        Cut Blanks to Produce
    </h3>
    <table>
        <thead>
            <tr>
                <th>Product Code</th>
                <th>Description</th>
                <th class="right">Qty to Make</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
        @foreach($cutLines as $line)
        <tr>
            <td style="font-family:monospace;font-size:9pt;">{{ $line->product->product_code ?? '—' }}</td>
            <td>{{ $line->product->description }}</td>
            <td class="qty">{{ number_format($line->qty) }}</td>
            <td class="note-cell">{{ $line->note }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif

    @if($madeLines->count())
    <h3 style="font-size:11pt;font-weight:700;color:#1e40af;margin-bottom:8px;background:#dbeafe;padding:6px 10px;border-radius:4px;">
        Made Collars to Produce
    </h3>
    <table>
        <thead>
            <tr>
                <th>Product Code</th>
                <th>Description</th>
                <th class="right">Qty to Make</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
        @foreach($madeLines as $line)
        <tr>
            <td style="font-family:monospace;font-size:9pt;">{{ $line->product->product_code ?? '—' }}</td>
            <td>{{ $line->product->description }}</td>
            <td class="qty">{{ number_format($line->qty) }}</td>
            <td class="note-cell">{{ $line->note }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
    @endif

    <div class="signature-area">
        <div class="sig-box"><label>Authorised by</label></div>
        <div class="sig-box"><label>Production received</label></div>
        <div class="sig-box"><label>Date completed</label></div>
    </div>

    <div class="footer">
        <span>Lockie Group — Collar Production Works Order #{{ $worksOrder->id }}</span>
        <span>Printed: {{ now()->format('d/m/Y H:i') }}</span>
    </div>

</div>
</body>
</html>
