<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Lockie Tender Radar</title>
<style>
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f1f5f9; margin: 0; padding: 0; }
  .wrap { max-width: 600px; margin: 24px auto; }
  .card { background: #fff; border-radius: 12px; padding: 0; overflow: hidden; margin-bottom: 16px; border: 1px solid #e2e8f0; }
  .header { background: #1e293b; color: #fff; padding: 20px 24px; }
  .header h1 { margin: 0; font-size: 1.1rem; font-weight: 700; }
  .header p { margin: 4px 0 0; font-size: 0.8rem; color: #94a3b8; }
  .section-title { padding: 12px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: 0.75rem; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
  .tender { padding: 14px 20px; border-bottom: 1px solid #f1f5f9; }
  .tender:last-child { border-bottom: none; }
  .tender-title { font-size: 0.9rem; font-weight: 700; color: #1e293b; margin: 0 0 5px; }
  .tender-meta { font-size: 0.78rem; color: #64748b; margin: 0 0 5px; }
  .tender-reason { font-size: 0.78rem; color: #475569; margin: 0; line-height: 1.4; }
  .badge { display: inline-block; font-size: 0.68rem; font-weight: 600; border-radius: 9999px; padding: 2px 8px; }
  .badge-high { background: #fef2f2; color: #dc2626; }
  .badge-relevant { background: #f0fdf4; color: #15803d; }
  .badge-possible { background: #fefce8; color: #a16207; }
  .score { font-size: 0.75rem; font-weight: 700; color: #94a3b8; }
  .link { color: #e11d48; text-decoration: none; font-size: 0.78rem; }
  .footer { text-align: center; padding: 16px; font-size: 0.75rem; color: #94a3b8; }
  .closing-badge { background: #fef2f2; color: #dc2626; font-size: 0.68rem; font-weight: 700; border-radius: 4px; padding: 1px 6px; }
</style>
</head>
<body>
<div class="wrap">

  <div class="card">
    <div class="header">
      <h1>🎯 Lockie Tender Radar</h1>
      <p>{{ now()->format('l, j F Y') }}</p>
    </div>

    {{-- Summary line --}}
    <div style="padding:12px 20px;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:0.8125rem;color:#334155;">
      @php
        $highCount     = $newHigh->where('ai_relevance','high')->count();
        $relevantCount = $newHigh->where('ai_relevance','relevant')->count();
        $closingCount  = $closingSoon->count();
        $possibleCount = $newPossible->count();
        $parts = [];
        if ($highCount)     $parts[] = "🔥 {$highCount} high-priority";
        if ($relevantCount) $parts[] = "🟢 {$relevantCount} relevant";
        if ($closingCount)  $parts[] = "⏰ {$closingCount} closing soon";
        if ($possibleCount) $parts[] = "🟡 {$possibleCount} possible";
      @endphp
      {{ implode(' · ', $parts) ?: 'No new opportunities today.' }}
    </div>

    {{-- High priority --}}
    @if($newHigh->where('ai_relevance','high')->count())
      <div class="section-title">🔥 High Priority</div>
      @foreach($newHigh->where('ai_relevance','high') as $tender)
        <div class="tender">
          <p class="tender-title">{{ $tender->title }}</p>
          <p class="tender-meta">
            @if($tender->buyer_name) {{ $tender->buyer_name }} &nbsp;·&nbsp; @endif
            {{ $tender->value_display }}
            @if($tender->deadline_at) &nbsp;·&nbsp; Closes {{ $tender->deadline_at->format('d M Y') }} @endif
            &nbsp;·&nbsp; <span class="score">{{ $tender->ai_score }}/100</span>
          </p>
          @if($tender->ai_reasoning)<p class="tender-reason">{{ $tender->ai_reasoning }}</p>@endif
          @if(!empty($tender->ai_products))
            <p style="margin:5px 0 0;font-size:0.72rem;color:#15803d;">{{ implode(' · ', $tender->ai_products) }}</p>
          @endif
          <p style="margin:6px 0 0;"><a href="{{ route('tender-radar.show', $tender) }}" class="link">View full details →</a></p>
        </div>
      @endforeach
    @endif

    {{-- Relevant --}}
    @if($newHigh->where('ai_relevance','relevant')->count())
      <div class="section-title">🟢 Relevant</div>
      @foreach($newHigh->where('ai_relevance','relevant') as $tender)
        <div class="tender">
          <p class="tender-title">{{ $tender->title }}</p>
          <p class="tender-meta">
            @if($tender->buyer_name) {{ $tender->buyer_name }} &nbsp;·&nbsp; @endif
            {{ $tender->value_display }}
            @if($tender->deadline_at) &nbsp;·&nbsp; Closes {{ $tender->deadline_at->format('d M Y') }} @endif
            &nbsp;·&nbsp; <span class="score">{{ $tender->ai_score }}/100</span>
          </p>
          @if($tender->ai_reasoning)<p class="tender-reason">{{ $tender->ai_reasoning }}</p>@endif
          <p style="margin:6px 0 0;"><a href="{{ route('tender-radar.show', $tender) }}" class="link">View →</a></p>
        </div>
      @endforeach
    @endif

    {{-- Closing soon --}}
    @if($closingSoon->count())
      <div class="section-title">⏰ Closing Soon (≤14 days)</div>
      @foreach($closingSoon as $tender)
        <div class="tender">
          <p class="tender-title">
            {{ $tender->title }}
            <span class="closing-badge">{{ $tender->deadline_at->diffInDays(now()) }}d left</span>
          </p>
          <p class="tender-meta">
            @if($tender->buyer_name) {{ $tender->buyer_name }} &nbsp;·&nbsp; @endif
            {{ $tender->value_display }} &nbsp;·&nbsp; Closes {{ $tender->deadline_at->format('d M Y') }}
          </p>
          <p style="margin:6px 0 0;"><a href="{{ route('tender-radar.show', $tender) }}" class="link">View →</a></p>
        </div>
      @endforeach
    @endif

    {{-- Possible --}}
    @if($newPossible->count())
      <div class="section-title">🟡 Also spotted (possible matches)</div>
      @foreach($newPossible as $tender)
        <div class="tender" style="padding:10px 20px;">
          <p style="margin:0;font-size:0.8rem;color:#475569;">
            <a href="{{ route('tender-radar.show', $tender) }}" class="link" style="color:#334155;font-weight:600;">{{ $tender->title }}</a>
            @if($tender->buyer_name) &nbsp;·&nbsp; {{ $tender->buyer_name }}@endif
            @if($tender->deadline_at) &nbsp;·&nbsp; Closes {{ $tender->deadline_at->format('d M') }}@endif
          </p>
        </div>
      @endforeach
    @endif

  </div>

  <div class="footer">
    <a href="{{ route('tender-radar.index') }}" style="color:#e11d48;text-decoration:none;font-weight:600;">Open Tender Radar →</a>
    <br><br>
    Lockie Tender Radar · Automated daily digest
  </div>

</div>
</body>
</html>
