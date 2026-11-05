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
                {{-- Customer search --}}
                <div class="sm:col-span-2 relative" id="customer-wrap">
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Customer Name <span class="text-red-400">*</span></label>
                    <input type="text" id="customer-search" autocomplete="off" required
                        placeholder="Start typing a customer name…"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500">
                    <input type="hidden" name="customer_name" id="customer-name-hidden">
                    <input type="hidden" name="customer_code" id="customer-code-hidden">
                    <div id="customer-dropdown"
                        class="hidden absolute z-20 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                    </div>
                    @error('customer_name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1">Customer Email</label>
                    <input type="email" name="customer_email" id="customer-email" value="{{ old('customer_email') }}"
                        class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500"
                        placeholder="optional">
                </div>
                <div>
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

            <div class="hidden sm:grid grid-cols-[1fr_80px_110px_100px_32px] gap-2 mb-2 text-[0.7rem] text-slate-400 uppercase tracking-wide font-semibold px-1">
                <span>Description / Product</span>
                <span class="text-right">Qty</span>
                <span class="text-right">Unit Price</span>
                <span class="text-right">Total</span>
                <span></span>
            </div>

            <div id="lines-container" class="space-y-2"></div>

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
// ── Helpers ──────────────────────────────────────────────────────────────────
function fmt(n) {
    return '£' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}
function escH(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
function escAttr(s) {
    return String(s ?? '').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}
function debounce(fn, ms) {
    let t; return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

// ── Customer autocomplete ────────────────────────────────────────────────────
const custSearch   = document.getElementById('customer-search');
const custDropdown = document.getElementById('customer-dropdown');
const custNameHid  = document.getElementById('customer-name-hidden');
const custCodeHid  = document.getElementById('customer-code-hidden');

custSearch.addEventListener('input', debounce(async () => {
    const q = custSearch.value.trim();
    custNameHid.value = q; // keep raw value as fallback
    custCodeHid.value = '';
    if (q.length < 2) { custDropdown.classList.add('hidden'); return; }

    const res  = await fetch(`{{ route('quotes.api.customers') }}?q=${encodeURIComponent(q)}`, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await res.json();
    if (!data.length) { custDropdown.classList.add('hidden'); return; }

    custDropdown.innerHTML = data.map(c => `
        <div class="cursor-pointer px-4 py-2.5 hover:bg-sky-50 text-sm border-b border-slate-50 last:border-0 flex justify-between items-center"
            onclick="selectCustomer('${escAttr(c.customer_name)}', '${escAttr(c.customer_code)}')">
            <span class="font-medium text-slate-800">${escH(c.customer_name)}</span>
            <span class="text-xs text-slate-400 font-mono">${escH(c.customer_code)}</span>
        </div>`).join('');
    custDropdown.classList.remove('hidden');
}, 220));

function selectCustomer(name, code) {
    custSearch.value  = name;
    custNameHid.value = name;
    custCodeHid.value = code;
    custDropdown.classList.add('hidden');
    // Update product price suggestions based on this customer
    document.querySelectorAll('.line-row').forEach(row => {
        const prodCode = row.dataset.productCode;
        if (prodCode) fetchProductPrice(row, prodCode, code);
    });
}

document.addEventListener('click', e => {
    if (!document.getElementById('customer-wrap').contains(e.target)) {
        custDropdown.classList.add('hidden');
    }
});

// ── Line items ────────────────────────────────────────────────────────────────
let lineIndex = 0;

function addLine(desc = '', qty = '', price = '', productCode = '') {
    const i   = lineIndex++;
    const div = document.createElement('div');
    div.className = 'line-row relative';
    div.dataset.productCode = productCode;
    div.innerHTML = `
        <div class="grid grid-cols-[1fr_80px_110px_100px_32px] gap-2 items-center">
            <div class="relative">
                <input type="text" name="lines[${i}][description]" value="${escAttr(desc)}" required
                    placeholder="Description or search product…" autocomplete="off"
                    class="desc-input w-full px-3 py-2 border border-slate-200 rounded-lg text-sm outline-none focus:ring-2 focus:ring-sky-500"
                    oninput="onDescInput(this, ${i})">
                <div class="prod-dropdown hidden absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden" data-idx="${i}"></div>
            </div>
            <input type="number" name="lines[${i}][quantity]" value="${escAttr(qty)}" required min="0.01" step="any" placeholder="Qty"
                class="qty-input px-2 py-2 border border-slate-200 rounded-lg text-sm text-right outline-none focus:ring-2 focus:ring-sky-500"
                oninput="recalc(this)">
            <input type="number" name="lines[${i}][unit_price]" value="${escAttr(price)}" required min="0" step="any" placeholder="0.00"
                class="price-input px-2 py-2 border border-slate-200 rounded-lg text-sm text-right outline-none focus:ring-2 focus:ring-sky-500"
                oninput="recalc(this)">
            <span class="line-total px-2 py-2 text-sm text-right text-slate-600 font-medium">—</span>
            <button type="button" onclick="removeLine(this)" class="text-slate-300 hover:text-red-400 transition-colors text-lg leading-none">✕</button>
        </div>`;
    document.getElementById('lines-container').appendChild(div);
    if (qty && price) recalcAll();
}

function removeLine(btn) {
    btn.closest('.line-row').remove();
    recalcAll();
}

// Product search on description field
const descSearches = {};
function onDescInput(input, idx) {
    const row = input.closest('.line-row');
    row.dataset.productCode = '';
    const q = input.value.trim();
    if (!descSearches[idx]) {
        descSearches[idx] = debounce(async (q, row) => {
            const dropdown = row.querySelector('.prod-dropdown');
            if (q.length < 2) { dropdown.classList.add('hidden'); return; }
            const customerCode = custCodeHid.value;
            const url = `{{ route('quotes.api.products') }}?q=${encodeURIComponent(q)}&customer_code=${encodeURIComponent(customerCode)}`;
            const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await res.json();
            if (!data.length) { dropdown.classList.add('hidden'); return; }
            dropdown.innerHTML = data.map(p => `
                <div class="cursor-pointer px-3 py-2 hover:bg-sky-50 text-sm border-b border-slate-50 last:border-0"
                    onclick="selectProduct(this, '${escAttr(p.product_code)}', '${escAttr(p.product_name ?? p.product_code)}', ${p.suggested_price ?? 'null'})">
                    <div class="flex justify-between items-center">
                        <span class="font-mono text-xs text-slate-500">${escH(p.product_code)}</span>
                        ${p.suggested_price ? `<span class="text-xs text-sky-600 font-semibold">${fmt(p.suggested_price)}</span>` : ''}
                    </div>
                    <div class="text-slate-700 text-xs mt-0.5">${escH(p.product_name ?? '')}</div>
                </div>`).join('');
            dropdown.classList.remove('hidden');
        }, 250);
    }
    descSearches[idx](q, row);
}

function selectProduct(el, code, name, price) {
    const row      = el.closest('.line-row');
    const dropdown = row.querySelector('.prod-dropdown');
    const descInput = row.querySelector('.desc-input');
    // Show "CODE — Name" in description
    descInput.value = code + (name && name !== code ? ' — ' + name : '');
    row.dataset.productCode = code;
    if (price !== null) {
        const priceInput = row.querySelector('.price-input');
        if (!priceInput.value) priceInput.value = price.toFixed(2);
    }
    dropdown.classList.add('hidden');
    recalc(row.querySelector('.qty-input'));
}

async function fetchProductPrice(row, productCode, customerCode) {
    const url = `{{ route('quotes.api.products') }}?q=${encodeURIComponent(productCode)}&customer_code=${encodeURIComponent(customerCode)}`;
    const res  = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();
    const match = data.find(p => p.product_code === productCode);
    if (match?.suggested_price) {
        const priceInput = row.querySelector('.price-input');
        priceInput.value = match.suggested_price.toFixed(2);
        recalc(priceInput);
    }
}

// Close product dropdowns on outside click
document.addEventListener('click', e => {
    if (!e.target.closest('.line-row')) {
        document.querySelectorAll('.prod-dropdown').forEach(d => d.classList.add('hidden'));
    }
});

// ── Totals ────────────────────────────────────────────────────────────────────
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
    document.getElementById('subtotal-display').textContent = fmt(subtotal);
    document.getElementById('vat-display').textContent      = fmt(vat);
    document.getElementById('total-display').textContent    = fmt(subtotal + vat);
}

// Form validation — ensure customer_name hidden is set
document.getElementById('quote-form').addEventListener('submit', function () {
    if (!custNameHid.value) custNameHid.value = custSearch.value.trim();
});

// Start with one blank line
addLine();
</script>
</x-layout>
