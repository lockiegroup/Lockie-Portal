<x-layout title="Historical Contract — Lockie Portal">
<main class="max-w-2xl mx-auto px-4 sm:px-6 py-8">

    <div style="margin-bottom:1.25rem;">
        <a href="{{ route('tender-radar.historical.index') }}" class="text-sm text-slate-400 hover:text-slate-600 transition-colors">← Contract History</a>
    </div>

    <h1 class="text-2xl font-bold text-slate-800 mb-6">
        {{ isset($contract) ? 'Edit Contract' : 'Add Historical Contract' }}
    </h1>

    @php $action = isset($contract) ? route('tender-radar.historical.update', $contract) : route('tender-radar.historical.store'); @endphp

    <form method="POST" action="{{ $action }}" style="display:flex;flex-direction:column;gap:16px;">
        @csrf
        @if(isset($contract)) @method('PUT') @endif

        @if($errors->any())
            <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:12px 16px;font-size:0.875rem;color:#dc2626;">
                @foreach($errors->all() as $e) <div>{{ $e }}</div> @endforeach
            </div>
        @endif

        @php
            $field = fn($name, $label, $type = 'text', $required = false) => <<<HTML
                <div>
                    <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">$label</label>
                HTML;
            $inputStyle = 'width:100%;font-size:0.875rem;border:1px solid #e2e8f0;border-radius:8px;padding:9px 12px;color:#334155;box-sizing:border-box;';
        @endphp

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Buyer <span style="color:#dc2626;">*</span></label>
                <input type="text" name="buyer" value="{{ old('buyer', $contract->buyer ?? '') }}" required
                    style="{{ $inputStyle }}" placeholder="e.g. Post Office Ltd">
            </div>
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Product Category <span style="color:#dc2626;">*</span></label>
                <input type="text" name="product_category" value="{{ old('product_category', $contract->product_category ?? '') }}" required
                    style="{{ $inputStyle }}" placeholder="e.g. Security Seals">
            </div>
        </div>

        <div>
            <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Description</label>
            <textarea name="description" rows="3" style="{{ $inputStyle }}resize:vertical;" placeholder="Brief description of the contract…">{{ old('description', $contract->description ?? '') }}</textarea>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Incumbent Supplier</label>
                <input type="text" name="incumbent_supplier" value="{{ old('incumbent_supplier', $contract->incumbent_supplier ?? '') }}"
                    style="{{ $inputStyle }}" placeholder="e.g. ITW Envopak">
            </div>
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Estimated Value (£)</label>
                <input type="number" name="value_estimate" value="{{ old('value_estimate', $contract->value_estimate ?? '') }}"
                    style="{{ $inputStyle }}" placeholder="250000" min="0">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Contract Start</label>
                <input type="date" name="contract_start" value="{{ old('contract_start', isset($contract) ? $contract->contract_start?->format('Y-m-d') : '') }}"
                    style="{{ $inputStyle }}">
            </div>
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Contract End</label>
                <input type="date" name="contract_end" value="{{ old('contract_end', isset($contract) ? $contract->contract_end?->format('Y-m-d') : '') }}"
                    style="{{ $inputStyle }}">
            </div>
        </div>

        <div>
            <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Extension Options</label>
            <input type="text" name="extension_options" value="{{ old('extension_options', $contract->extension_options ?? '') }}"
                style="{{ $inputStyle }}" placeholder="e.g. 1 × 12 month extension">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Status <span style="color:#dc2626;">*</span></label>
                <select name="status" required style="{{ $inputStyle }}">
                    @foreach(['monitoring' => 'Monitoring', 'replacement_found' => 'Replacement Found', 'closed' => 'Closed'] as $val => $lbl)
                        <option value="{{ $val }}" {{ old('status', $contract->status ?? 'monitoring') === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Link to Tender</label>
                <select name="related_tender_id" style="{{ $inputStyle }}">
                    <option value="">None</option>
                    @foreach($tenders as $t)
                        <option value="{{ $t->id }}" {{ old('related_tender_id', $contract->related_tender_id ?? '') == $t->id ? 'selected' : '' }}>
                            {{ Str::limit($t->title, 50) }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label style="display:block;font-size:0.8125rem;font-weight:600;color:#374151;margin-bottom:4px;">Notes</label>
            <textarea name="notes" rows="3" style="{{ $inputStyle }}resize:vertical;" placeholder="Any additional notes…">{{ old('notes', $contract->notes ?? '') }}</textarea>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;padding-top:4px;">
            <a href="{{ route('tender-radar.historical.index') }}"
                style="padding:9px 18px;border-radius:8px;border:1px solid #e2e8f0;color:#475569;font-size:0.875rem;text-decoration:none;">
                Cancel
            </a>
            <button type="submit"
                style="padding:9px 20px;border-radius:8px;background:#1e293b;color:#fff;font-size:0.875rem;font-weight:600;border:none;cursor:pointer;">
                {{ isset($contract) ? 'Save changes' : 'Add contract' }}
            </button>
        </div>
    </form>

</main>
</x-layout>
