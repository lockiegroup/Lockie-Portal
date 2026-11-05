<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $quote->quote_number }} — JW Products</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        font-size: 13px;
        color: #1e293b;
        background: #fff;
        padding: 48px 56px;
        max-width: 820px;
        margin: 0 auto;
    }

    /* ── Header ── */
    .header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 40px;
        padding-bottom: 24px;
        border-bottom: 2px solid #0f172a;
    }
    .logo-block {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }
    .logo-img {
        height: 60px;
        width: auto;
    }
    .company-address {
        text-align: right;
        font-size: 11px;
        color: #64748b;
        line-height: 1.6;
    }

    /* ── Quote title ── */
    .quote-meta {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 32px;
    }
    .quote-title {
        font-size: 28px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: -0.5px;
    }
    .quote-number {
        font-size: 13px;
        color: #64748b;
        margin-top: 4px;
        font-family: 'Courier New', monospace;
    }
    .quote-info {
        text-align: right;
        font-size: 12px;
        color: #64748b;
        line-height: 1.8;
    }
    .quote-info strong { color: #1e293b; }

    /* ── Bill to ── */
    .bill-to {
        background: #f8fafc;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 32px;
        border: 1px solid #e2e8f0;
    }
    .bill-to-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 4px;
    }
    .bill-to-name {
        font-size: 15px;
        font-weight: 600;
        color: #0f172a;
    }
    .bill-to-email {
        font-size: 12px;
        color: #64748b;
        margin-top: 2px;
    }
    .reference-row {
        margin-top: 6px;
        font-size: 12px;
        color: #64748b;
    }
    .reference-row strong { color: #334155; }

    /* ── Table ── */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    thead tr {
        background: #0f172a;
        color: #fff;
    }
    thead th {
        padding: 10px 14px;
        font-size: 10px;
        font-weight: 600;
        letter-spacing: 0.06em;
        text-transform: uppercase;
    }
    thead th:first-child { border-radius: 6px 0 0 0; }
    thead th:last-child  { border-radius: 0 6px 0 0; }
    thead th.right { text-align: right; }
    tbody tr { border-bottom: 1px solid #f1f5f9; }
    tbody tr:last-child { border-bottom: none; }
    tbody td {
        padding: 11px 14px;
        font-size: 12.5px;
        color: #334155;
    }
    tbody td.right { text-align: right; color: #1e293b; }
    tbody td.total-col { font-weight: 600; }

    .table-wrap {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    /* ── Totals ── */
    .totals {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 32px;
    }
    .totals-box {
        width: 240px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
    }
    .totals-row {
        display: flex;
        justify-content: space-between;
        padding: 9px 14px;
        font-size: 12.5px;
        color: #475569;
        border-bottom: 1px solid #f1f5f9;
    }
    .totals-row:last-child {
        border-bottom: none;
        background: #f8fafc;
        font-weight: 700;
        font-size: 14px;
        color: #0f172a;
    }

    /* ── Notes ── */
    .notes-section {
        margin-bottom: 40px;
    }
    .notes-label {
        font-size: 10px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #94a3b8;
        margin-bottom: 6px;
    }
    .notes-text {
        font-size: 12px;
        color: #64748b;
        line-height: 1.7;
        white-space: pre-line;
    }

    /* ── Footer ── */
    .footer {
        border-top: 1px solid #e2e8f0;
        padding-top: 16px;
        text-align: center;
        font-size: 11px;
        color: #94a3b8;
    }

    /* ── Print ── */
    @media print {
        body { padding: 20px 28px; }
        .no-print { display: none !important; }
        @page { margin: 0.6cm; size: A4; }
    }
</style>
</head>
<body>

{{-- Print button (hidden when printing) --}}
<div class="no-print" style="text-align:right;margin-bottom:24px;">
    <button onclick="window.print()"
        style="padding:8px 20px;background:#0f172a;color:#fff;border:none;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">
        Print / Save as PDF
    </button>
</div>

{{-- Header --}}
<div class="header">
    <div class="logo-block">
        <img src="{{ asset('images/jwp-logo.webp') }}" alt="JWP Security Seals" class="logo-img">
    </div>
    <div class="company-address">
        JWP Security Seals Ltd<br>
        Haydock, Merseyside<br>
        info@jwproducts.co.uk<br>
        www.jwproducts.co.uk
    </div>
</div>

{{-- Quote title + meta --}}
<div class="quote-meta">
    <div>
        <div class="quote-title">Quotation</div>
        <div class="quote-number">{{ $quote->quote_number }}</div>
    </div>
    <div class="quote-info">
        <div><strong>Date:</strong> {{ $quote->created_at->format('d F Y') }}</div>
        <div><strong>Valid for:</strong> 30 days</div>
        @if($quote->createdBy)
        <div><strong>Prepared by:</strong> {{ $quote->createdBy->name }}</div>
        @endif
    </div>
</div>

{{-- Bill to --}}
<div class="bill-to">
    <div class="bill-to-label">Prepared for</div>
    <div class="bill-to-name">{{ $quote->customer_name }}</div>
    @if($quote->customer_email)
    <div class="bill-to-email">{{ $quote->customer_email }}</div>
    @endif
    @if($quote->reference)
    <div class="reference-row"><strong>Reference:</strong> {{ $quote->reference }}</div>
    @endif
</div>

{{-- Line items --}}
<div class="table-wrap">
    <table>
        <thead>
            <tr>
                <th style="text-align:left;width:50%">Description</th>
                <th class="right">Qty</th>
                <th class="right">Unit Price</th>
                <th class="right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($quote->lines as $line)
            <tr>
                <td>{{ $line->description }}</td>
                <td class="right">{{ rtrim(rtrim(number_format($line->quantity, 2), '0'), '.') }}</td>
                <td class="right">£{{ number_format($line->unit_price, 2) }}</td>
                <td class="right total-col">£{{ number_format($line->line_total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Totals --}}
@php $vat = $quote->total_value * 0.2; @endphp
<div class="totals">
    <div class="totals-box">
        <div class="totals-row">
            <span>Subtotal (ex VAT)</span>
            <span>£{{ number_format($quote->total_value, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>VAT (20%)</span>
            <span>£{{ number_format($vat, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Total (inc VAT)</span>
            <span>£{{ number_format($quote->total_value + $vat, 2) }}</span>
        </div>
    </div>
</div>

{{-- Notes --}}
@if($quote->notes)
<div class="notes-section">
    <div class="notes-label">Notes</div>
    <div class="notes-text">{{ $quote->notes }}</div>
</div>
@endif

{{-- Footer --}}
<div class="footer">
    JWP Security Seals Ltd &nbsp;·&nbsp; Registered in England &amp; Wales &nbsp;·&nbsp; www.jwproducts.co.uk
</div>

</body>
</html>
