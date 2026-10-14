<div style="display:flex;gap:0.5rem;margin-top:0.5rem;flex-wrap:wrap;align-items:flex-end;">
    <div>
        <label style="font-size:0.7rem;color:#94a3b8;display:block;margin-bottom:2px;">Email</label>
        <input type="email" id="email-{{ $formId }}" placeholder="name@example.com" maxlength="200"
            style="padding:0.35rem 0.6rem;border:1px solid #e2e8f0;border-radius:6px;font-size:0.8125rem;width:220px;">
    </div>
    <div>
        <label style="font-size:0.7rem;color:#94a3b8;display:block;margin-bottom:2px;">Label <span style="color:#cbd5e1;">(optional)</span></label>
        <input type="text" id="label-{{ $formId }}" placeholder="e.g. Site Manager" maxlength="100"
            style="padding:0.35rem 0.6rem;border:1px solid #e2e8f0;border-radius:6px;font-size:0.8125rem;width:160px;">
    </div>
    <button onclick="addRecipient('{{ $categoryId }}', '{{ $formId }}')"
        style="padding:0.35rem 0.875rem;background:#1e293b;color:#fff;border:none;border-radius:6px;font-size:0.8125rem;font-weight:600;cursor:pointer;">
        + Add email
    </button>
    <span id="res-{{ $formId }}" style="font-size:0.8125rem;"></span>
</div>
