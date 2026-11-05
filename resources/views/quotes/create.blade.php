<x-layout title="New Quote">
<main class="max-w-3xl mx-auto px-4 sm:px-6 py-8">

    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">New Quote</h1>
            <p class="text-slate-500 mt-0.5 text-sm font-mono">{{ $nextNumber }}</p>
        </div>
        <a href="{{ route('quotes.index') }}" class="text-sm text-slate-500 hover:text-slate-800 transition-colors">← Back</a>
    </div>

    <form method="POST" action="{{ route('quotes.store') }}" id="quote-form">
        @csrf

        {{-- Customer details --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
            <h2 class="font-semibold text-slate-800 mb-4">Customer</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Customer Name <span class="text-red-400">*</span></label>
                    <input type="text" name="customer_name" value="{{ old('customer_name') }}" required
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500"
                        placeholder="e.g. Acme Ltd">
                    @error('customer_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Customer Email</label>
                    <input type="email" name="customer_email" value="{{ old('customer_email') }}"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500"
                        placeholder="optional">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Your Reference</label>
                    <input type="text" name="reference" value="{{ old('reference') }}"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500"
                        placeholder="e.g. order ref, project name">
                </div>
            </div>
        </div>

        {{-- Line items --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
            <h2 class="font-semibold text-slate-800 mb-4">Line Items</h2>

            <div class="hidden sm:grid grid-cols-[1fr_80px_100px_100px_32px] gap-2 mb-2 text-[0.7rem] text-slate-400 uppercase tracking-wide font-semibold px-1">
                <span>Description</span>
                <span class="text-right">Qty</span>
                <span class="text-right">Unit Price</span>
                <span class="text-right">Total</span>
                <span></span>
            </div>

            <div id="lines-container" class="space-y-2">
                {{-- rows injected by JS --}}
            </div>

            <button type="button" onclick="addLine()"
                class="mt-3 px-3 py-1.5 text-xs font-semibold text-slate-500 border border-dashed border-slate-300 rounded-lg hover:bg-slate-50 transition-colors w-full">
                + Add Line
            </button>

            @error('lines')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror
        </div>

        {{-- Totals --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-5">
            <div class="flex justify-end">
                <div class="w-56 space-y-2">
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>Subtotal (ex VAT)</span>
                        <span id="subtotal-display">£0.00</span>
                    </div>
                    <div class="flex justify-between text-sm text-slate-600">
                        <span>VAT (20%)</span>
                        <span id="vat-display">£0.00</span>
                    </div>
                    <div class="flex justify-between font-bold text-slate-800 border-t border-slate-100 pt-2">
                        <span>Total (inc VAT)</span>
                        <span id="total-display">£0.00</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Notes --}}
        <div class="bg-white rounded-xl border border-slate-200 p-5 mb-6">
            <h2 class="font-semibold text-slate-800 mb-3">Notes <span class="text-slate-400 font-normal text-xs">(shown on quote)</span></h2>
            <textarea name="notes" rows="3"
                class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500 resize-none"
                placeholder="e.g. Prices valid for 30 days. Delivery not included.">{{ old('notes') }}</textarea>
        </div>

        <div class="flex gap-3 justify-end">
            <a href="{{ route('quotes.index') }}"
                class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-semibold rounded-lg hover:bg-slate-50 transition-colors">
                Cancel
            </a>
            <button type="submit"
                class="px-6 py-2 bg-slate-900 hover:bg-slate-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Save Quote
            </button>
        </div>
    </form>

</main>

<script>
let lineIndex = 0;

function fmt(n) {
    return '£' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function addLine(desc = '', qty = '', price = '') {
    const i = lineIndex++;
    const div = document.createElement('div');
    div.className = 'line-row grid grid-cols-[1fr_80px_100px_100px_32px] gap-2 items-center';
    div.innerHTML = `
        <input type="text" name="lines[${i}][description]" value="${escAttr(desc)}" required placeholder="Description"
            class="px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500">
        <input type="number" name="lines[${i}][quantity]" value="${escAttr(qty)}" required min="0.01" step="any" placeholder="Qty"
            class="qty-input px-2 py-2 border border-slate-200 rounded-lg text-sm text-right outline-none focus:ring-2 focus:ring-sky-500"
            oninput="recalc(this)">
        <input type="number" name="lines[${i}][unit_price]" value="${escAttr(price)}" required min="0" step="any" placeholder="0.00"
            class="price-input px-2 py-2 border border-slate-200 rounded-lg text-sm text-right outline-none focus:ring-2 focus:ring-sky-500"
            oninput="recalc(this)">
        <span class="line-total px-2 py-2 text-sm text-right text-slate-600 font-medium">—</span>
        <button type="button" onclick="removeLine(this)" class="text-slate-300 hover:text-red-400 transition-colors text-lg leading-none">✕</button>`;
    document.getElementById('lines-container').appendChild(div);
    recalcAll();
}

function removeLine(btn) {
    btn.closest('.line-row').remove();
    recalcAll();
}

function recalc(input) {
    const row   = input.closest('.line-row');
    const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
    const price = parseFloat(row.querySelector('.price-input').value) || 0;
    const total = qty * price;
    row.querySelector('.line-total').textContent = total > 0 ? fmt(total) : '—';
    recalcAll();
}

function recalcAll() {
    let subtotal = 0;
    document.querySelectorAll('.line-row').forEach(row => {
        const qty   = parseFloat(row.querySelector('.qty-input').value) || 0;
        const price = parseFloat(row.querySelector('.price-input').value) || 0;
        subtotal += qty * price;
    });
    const vat   = subtotal * 0.2;
    const total = subtotal + vat;
    document.getElementById('subtotal-display').textContent = fmt(subtotal);
    document.getElementById('vat-display').textContent      = fmt(vat);
    document.getElementById('total-display').textContent    = fmt(total);
}

function escAttr(s) {
    return String(s ?? '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
}

// Start with one blank line
addLine();
</script>
</x-layout>
