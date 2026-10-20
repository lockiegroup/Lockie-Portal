<x-layout title="{{ $category->name }} — Stock Watchlist">

<style>
.sw-wrap { overflow-x: auto; overflow-y: auto; max-height: calc(100vh - 240px); }
.sw-table { border-collapse: collapse; font-size: 0.8rem; min-width: 100%; white-space: nowrap; }
.sw-table th {
    background: #0f172a; color: #e2e8f0;
    padding: 7px 10px; text-align: right; font-weight: 600;
    position: sticky; top: 0; z-index: 2;
    border-right: 1px solid #1e293b;
}
.sw-table th:first-child, .sw-table th:nth-child(2) { text-align: left; }
.sw-table td {
    padding: 5px 10px; border-bottom: 1px solid #f1f5f9; border-right: 1px solid #f1f5f9;
    color: #334155; text-align: right; vertical-align: middle;
}
.sw-table td:first-child, .sw-table td:nth-child(2) { text-align: left; }
.sw-req-click { cursor: pointer; text-decoration: underline dotted #b45309; }
.sw-req-click:hover { background: #fef3c7; border-radius: 3px; }
.sw-flash { background: #dcfce7 !important; transition: background 0.6s; }
.sw-table tr:hover td { background: #f8fafc; }
.sw-num { font-variant-numeric: tabular-nums; }
.sw-input {
    width: 70px; text-align: right; border: 1px solid transparent;
    border-radius: 4px; padding: 2px 5px; font-size: 0.8rem; color: #0f172a;
    background: transparent; font-variant-numeric: tabular-nums;
}
.sw-input:hover { border-color: #cbd5e1; background: white; }
.sw-input:focus { border-color: #6366f1; outline: none; background: white; }
.sw-badge {
    display: inline-block; padding: 1px 7px; border-radius: 10px;
    font-size: 0.7rem; font-weight: 600;
}
.sw-badge-ok   { background: #dcfce7; color: #166534; }
.sw-badge-low  { background: #fef9c3; color: #854d0e; }
.sw-badge-out  { background: #fee2e2; color: #991b1b; }
.sw-badge-disc { background: #f1f5f9; color: #94a3b8; }
.sw-disc-row td { background: #fecaca; color: #7f1d1d; }
.sw-disc-row:hover td { background: #fca5a5; }
.sw-add-row td { background: #fafafa; }
.sw-filter-input {
    width: 100%; box-sizing: border-box;
    border: 1px solid #334155; border-radius: 3px;
    padding: 2px 5px; font-size: 0.72rem; color: #e2e8f0;
    background: #1e293b;
}
.sw-filter-input:focus { outline: none; border-color: #6366f1; }
.sw-filter-input::placeholder { color: #64748b; }
.sw-filter-input option { background: #1e293b; color: #e2e8f0; }
.btn-del {
    background: none; border: none; cursor: pointer;
    color: #cbd5e1; padding: 2px 4px; border-radius: 4px;
    line-height:0; transition: color 0.15s;
}
.btn-del:hover { color: #ef4444; }
.btn-ghost {
    background: none; border: 1px solid #e2e8f0; color: #64748b;
    padding: 5px 12px; border-radius: 6px; font-size: 0.8rem;
    cursor: pointer; transition: all 0.15s;
}
.btn-ghost:hover { border-color: #94a3b8; color: #334155; }
.info-input {
    border: 1px solid transparent; border-radius: 4px;
    padding: 2px 5px; font-size: 0.8rem; color: #64748b;
    background: transparent; width: 140px;
}
.info-input:hover { border-color: #cbd5e1; background: white; }
.info-input:focus { border-color: #6366f1; outline: none; background: white; color: #334155; }
</style>

<main class="px-4 sm:px-6 py-8 max-w-full">

    {{-- Header --}}
    <div class="mb-4">
        <a href="{{ route('stock-watchlist.index') }}"
           class="text-[0.8rem] text-slate-500 no-underline inline-flex items-center gap-1 mb-2.5 hover:text-slate-700">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
            Stock Watchlist
        </a>
        <div class="flex items-start justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-[1.4rem] font-bold text-slate-800 mb-0.5">{{ $category->name }}</h1>
                <div class="flex items-center gap-3.5 flex-wrap mt-1">
                    <label class="flex items-center gap-1.5 text-[0.78rem] text-slate-500 cursor-default">
                        Lead time:
                        <input type="number" min="1" max="3650"
                            value="{{ $category->lead_time_days ?? 30 }}"
                            class="w-13 border border-slate-300 rounded px-1.5 py-0.5 text-[0.78rem] font-semibold text-slate-900 text-center"
                            onblur="saveCatField('lead_time_days', this.value)"
                            onkeydown="if(event.key==='Enter')this.blur()">
                        days
                    </label>
                    <label class="flex items-center gap-1.5 text-[0.78rem] text-slate-500 cursor-default">
                        Currency:
                        <select onchange="saveCatCurrency(this.value)"
                            class="border border-slate-300 rounded px-1.5 py-0.5 text-[0.78rem] font-semibold text-slate-900 bg-white">
                            @foreach(['£','$','€','AU$','NZ$'] as $cur)
                            <option value="{{ $cur }}" {{ ($category->currency ?? '£') === $cur ? 'selected' : '' }}>{{ $cur }}</option>
                            @endforeach
                        </select>
                    </label>
                    <a href="{{ route('stock-watchlist.items.download', $category) }}"
                        class="text-[0.78rem] text-blue-500 no-underline hover:text-blue-700">↓ Export CSV</a>
                    <button onclick="document.getElementById('cat-import-file').click()"
                        class="bg-transparent border-none text-[0.78rem] text-blue-500 cursor-pointer p-0 hover:text-blue-700">↑ Import CSV</button>
                    <input type="file" id="cat-import-file" accept=".csv,.txt" style="display:none"
                        onchange="importCatItems(this)">
                    <span class="text-slate-200">|</span>
                    <button onclick="document.getElementById('shopify-upload-file').click()"
                        class="bg-transparent border-none text-[0.78rem] text-violet-600 cursor-pointer p-0 hover:text-violet-800"
                        title="Upload Shopify products export CSV">
                        🌐 Shopify: <span id="shopify-sku-count">{{ $category->shopify_skus !== null ? number_format(count($category->shopify_skus)) . ' SKUs' : 'not set' }}</span>
                    </button>
                    <input type="file" id="shopify-upload-file" accept=".csv,.txt" style="display:none"
                        onchange="uploadPlatformFile(this, 'shopify', 'shopify-sku-count')">
                    <button onclick="document.getElementById('amazon-upload-file').click()"
                        class="bg-transparent border-none text-[0.78rem] text-amber-600 cursor-pointer p-0 hover:text-amber-800"
                        title="Upload Amazon inventory report TXT">
                        📦 Amazon: <span id="amazon-sku-count">{{ $category->amazon_skus !== null ? number_format(count($category->amazon_skus)) . ' SKUs' : 'not set' }}</span>
                    </button>
                    <input type="file" id="amazon-upload-file" accept=".csv,.txt" style="display:none"
                        onchange="uploadPlatformFile(this, 'amazon', 'amazon-sku-count')">
                </div>
                <form method="POST" action="{{ route('stock-watchlist.sales.filter') }}"
                      class="flex items-center gap-1.5 mt-2 flex-wrap">
                    @csrf
                    <span class="text-[0.75rem] text-slate-500 font-medium">Sales period:</span>
                    <input type="date" name="sales_from" value="{{ $filterFrom }}"
                           class="border border-slate-300 rounded-md px-1.5 py-0.5 text-[0.75rem] text-slate-800">
                    <span class="text-[0.75rem] text-slate-400">to</span>
                    <input type="date" name="sales_to" value="{{ $filterTo }}"
                           class="border border-slate-300 rounded-md px-1.5 py-0.5 text-[0.75rem] text-slate-800">
                    <button type="submit" class="btn-ghost" style="padding:3px 10px;font-size:0.75rem;">Apply</button>
                </form>
                @if($salesFrom)
                <p class="text-[0.7rem] text-slate-400 mt-0.5">Data covers: <span class="text-slate-500 font-medium">{{ $salesFrom }} – {{ $salesTo }}</span></p>
                @endif
            </div>
            <div class="flex items-center gap-2.5 flex-shrink-0 flex-wrap">
                <span id="sync-status" class="text-[0.8rem] text-slate-400">
                    @if($syncedAt) Last synced {{ \Carbon\Carbon::parse($syncedAt)->diffForHumans() }} @else Not yet synced @endif
                </span>
                <button class="btn-ghost" onclick="clearCatOrders()" style="color:#dc2626;border-color:#fca5a5;">Clear To Order</button>
                <button id="sync-btn" onclick="runSync()"
                    class="flex items-center gap-1.5 px-4 py-2 bg-slate-900 text-white border-none rounded-lg text-sm font-semibold cursor-pointer hover:bg-slate-800 transition-colors">
                    <svg id="sync-icon" class="w-[15px] h-[15px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                        <polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/>
                        <path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>
                    </svg>
                    Sync from Unleashed
                </button>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="bg-white border border-slate-200 rounded-xl overflow-clip">
        <div class="flex items-center justify-between px-3 py-2 border-b border-slate-100 bg-slate-50">
            <span id="sw-filter-count" class="text-[0.75rem] text-slate-400"></span>
            <div class="flex items-center gap-3">
                <button onclick="exportTableCsv()" class="text-[0.75rem] text-cyan-600 bg-transparent border-none cursor-pointer p-0 hover:text-cyan-800">↓ Export table</button>
                <button onclick="clearFilters()" class="text-[0.75rem] text-indigo-500 bg-transparent border-none cursor-pointer p-0 hover:text-indigo-700">Clear filters</button>
            </div>
        </div>
        <div class="sw-wrap">
            <table class="sw-table">
                <thead>
                    <tr>
                        <th style="text-align:left;min-width:110px;">Code</th>
                        <th style="text-align:left;min-width:140px;">Notes</th>
                        <th style="text-align:left;min-width:120px;">Product Group</th>
                        @foreach($years as $yr)
                            <th>{{ $yr }}<br><small style="font-weight:400;opacity:0.7;">units</small></th>
                        @endforeach
                        <th>Avg/Mo</th>
                        <th>On Hand</th>
                        <th>Stock Value</th>
                        <th>Alloc'd</th>
                        <th>On Order</th>
                        <th style="cursor:pointer;user-select:none;" onclick="fillAllRequired()" title="Fill all To Order from Required">Required<br><small style="font-weight:400;opacity:0.7;">click→order</small></th>
                        <th>To Order</th>
                        <th>Price</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Disc</th>
                        <th>On Web</th>
                        <th>On Amazon</th>
                    </tr>
                    @php $yc = count($years); @endphp
                    <tr class="bg-slate-800">
                        <td class="px-1 py-0.5"><input class="col-filter sw-filter-input" data-col="0" data-type="text" placeholder="Code…" oninput="applyFilters()"></td>
                        <td class="px-1 py-0.5"><input class="col-filter sw-filter-input" data-col="1" data-type="text" placeholder="Notes…" oninput="applyFilters()"></td>
                        <td class="px-1 py-0.5"><input class="col-filter sw-filter-input" data-col="2" data-type="text" placeholder="Group…" oninput="applyFilters()"></td>
                        @foreach($years as $yi => $yr)
                        <td class="px-1 py-0.5"><input class="col-filter sw-filter-input" data-col="{{ 3 + $yi }}" data-type="num" placeholder=">0" style="width:52px;text-align:right;" oninput="applyFilters()"></td>
                        @endforeach
                        <td class="px-1 py-0.5"><input class="col-filter sw-filter-input" data-col="{{ 3+$yc }}" data-type="num" placeholder=">0" style="width:52px;text-align:right;" oninput="applyFilters()"></td>
                        <td class="px-1 py-0.5"><input class="col-filter sw-filter-input" data-col="{{ 3+$yc+1 }}" data-type="num" placeholder="=0" style="width:52px;text-align:right;" oninput="applyFilters()"></td>
                        <td></td>{{-- Stock Value --}}
                        <td></td>{{-- Alloc'd --}}
                        <td></td>{{-- On Order --}}
                        <td class="px-1 py-0.5"><input class="col-filter sw-filter-input" data-col="{{ 3+$yc+5 }}" data-type="num" placeholder=">0" style="width:52px;text-align:right;" oninput="applyFilters()"></td>
                        <td></td>{{-- To Order --}}
                        <td></td>{{-- Price --}}
                        <td></td>{{-- Total --}}
                        <td class="px-1 py-0.5">
                            <select class="col-filter sw-filter-input" data-col="{{ 3+$yc+9 }}" data-type="select" onchange="applyFilters()">
                                <option value="">All</option>
                                <option value="OK">OK</option>
                                <option value="Order Needed">Order Needed</option>
                                <option value="Out of Stock">Out of Stock</option>
                                <option value="No Data">No Data</option>
                                <option value="Discontinued">Discontinued</option>
                            </select>
                        </td>
                        <td class="px-1 py-0.5">
                            <select class="col-filter sw-filter-input" data-type="disc" onchange="applyFilters()">
                                <option value="">All</option>
                                <option value="active">Active</option>
                                <option value="disc">Disc'd</option>
                            </select>
                        </td>
                        <td class="px-1 py-0.5">
                            <select class="col-filter sw-filter-input" data-type="platform" data-platform="shopify" onchange="applyFilters()">
                                <option value="">All</option>
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                        </td>
                        <td class="px-1 py-0.5">
                            <select class="col-filter sw-filter-input" data-type="platform" data-platform="amazon" onchange="applyFilters()">
                                <option value="">All</option>
                                <option value="yes">Yes</option>
                                <option value="no">No</option>
                            </select>
                        </td>
                    </tr>
                </thead>

                {{-- Sortable item rows --}}
                @php
                    $shopifySet = $category->shopify_skus !== null ? array_flip($category->shopify_skus) : null;
                    $amazonSet  = $category->amazon_skus  !== null ? array_flip($category->amazon_skus)  : null;
                @endphp
                <tbody class="sw-sortable" id="sortable-items">
                @forelse($category->items as $item)
                @php
                    $stock     = $item->stock;
                    $onHand    = $stock ? (float)$stock->qty_on_hand : null;
                    $allocated = $stock ? (float)$stock->qty_allocated : 0;
                    $onOrder   = $stock ? (float)$stock->qty_on_order : 0;
                    $avgMo     = $item->avg_monthly;
                    $reqQty    = $item->discontinued ? 0 : $item->required_qty;
                    $toOrder   = (float)($item->to_order_qty ?? 0);
                    $price     = (float)($item->unit_price ?? 0);
                    $total     = $toOrder * $price;

                    if ($item->discontinued) {
                        $badgeClass = 'sw-badge-disc'; $badgeText = 'Discontinued';
                    } elseif ($onHand === null) {
                        $badgeClass = 'sw-badge-low'; $badgeText = 'No Data';
                    } elseif (($onHand - $allocated) <= 0) {
                        if ($reqQty > 0) {
                            $badgeClass = 'sw-badge-low'; $badgeText = 'Order Needed';
                        } else {
                            $badgeClass = 'sw-badge-out'; $badgeText = 'Out of Stock';
                        }
                    } elseif ($reqQty > 0) {
                        $badgeClass = 'sw-badge-low'; $badgeText = 'Order Needed';
                    } else {
                        $badgeClass = 'sw-badge-ok'; $badgeText = 'OK';
                    }
                @endphp
                <tr class="{{ $item->discontinued ? 'sw-disc-row' : '' }}" data-id="{{ $item->id }}"
                    data-group="{{ $item->product_group ?? '' }}"
                    data-status="{{ $badgeText }}">
                    <td class="font-semibold text-slate-900">{{ $item->product_code }}</td>
                    <td>
                        <input type="text" class="info-input"
                            value="{{ $item->info }}"
                            placeholder="Add notes…"
                            onblur="saveField({{ $item->id }}, 'info', this.value)"
                            onkeydown="if(event.key==='Enter')this.blur()">
                    </td>
                    <td class="text-slate-500 text-[0.78rem]">{{ $item->product_group ?? '—' }}</td>
                    @foreach($years as $yr)
                    <td class="sw-num">
                        @php $yrQty = $item->yearly[$yr] ?? 0; @endphp
                        {{ $yrQty > 0 ? number_format($yrQty, 0) : '—' }}
                    </td>
                    @endforeach
                    <td class="sw-num">{{ $avgMo > 0 ? number_format($avgMo, 1) : '—' }}</td>
                    <td class="sw-num {{ ($onHand !== null && ($onHand - $allocated) <= 0) ? 'text-red-600' : '' }}">
                        {{ $onHand !== null ? number_format($onHand, 0) : '—' }}
                    </td>
                    @php $stockValue = $stock ? (float)$stock->total_cost : null; @endphp
                    <td class="sw-num text-slate-600 font-medium">
                        {{ $stockValue ? '£' . number_format($stockValue, 2) : '—' }}
                    </td>
                    <td class="sw-num text-slate-400">
                        {{ $allocated > 0 ? number_format($allocated, 0) : '—' }}
                    </td>
                    <td class="sw-num text-cyan-600">
                        {{ $onOrder > 0 ? number_format($onOrder, 0) : '—' }}
                    </td>
                    <td class="sw-num {{ $reqQty > 0 ? 'sw-req-click' : '' }}"
                        style="{{ $reqQty > 0 ? 'color:#b45309;font-weight:700;' : 'color:#94a3b8;' }}"
                        data-item-id="{{ $item->id }}" data-req-qty="{{ $reqQty }}"
                        @if($reqQty > 0) onclick="copyRequired(this)" title="Click to copy to To Order" @endif>
                        {{ $reqQty > 0 ? number_format($reqQty, 0) : '—' }}
                    </td>
                    <td>
                        <input type="number" class="sw-input" min="0" step="1"
                            data-field="to_order"
                            value="{{ $toOrder > 0 ? (int)$toOrder : '' }}"
                            placeholder="{{ $reqQty > 0 ? $reqQty : '0' }}"
                            onblur="saveField({{ $item->id }}, 'to_order_qty', this.value); recalcTotal(this.closest('tr'))"
                            onkeydown="if(event.key==='Enter')this.blur()">
                    </td>
                    <td>
                        <input type="number" class="sw-input" min="0" step="0.01"
                            data-field="unit_price"
                            value="{{ $price > 0 ? number_format($price, 2, '.', '') : '' }}"
                            placeholder="0.00"
                            onblur="saveField({{ $item->id }}, 'unit_price', this.value); recalcTotal(this.closest('tr'))"
                            onkeydown="if(event.key==='Enter')this.blur()">
                    </td>
                    <td class="sw-num sw-total font-semibold" data-amount="{{ $total > 0 ? number_format($total, 2, '.', '') : '' }}" data-currency="{{ $category->currency ?? '£' }}"></td>
                    <td><span class="sw-badge {{ $badgeClass }}">{{ $badgeText }}</span></td>
                    <td class="text-center">
                        <input type="checkbox" {{ $item->discontinued ? 'checked' : '' }}
                            onchange="toggleDiscontinued(this, {{ $item->id }})"
                            class="w-3.5 h-3.5 cursor-pointer accent-indigo-500">
                    </td>
                    @php
                        $onWeb     = $shopifySet !== null ? isset($shopifySet[$item->product_code]) : null;
                        $onAmazon  = $amazonSet  !== null ? isset($amazonSet[$item->product_code])  : null;
                    @endphp
                    <td class="text-center" data-platform-shopify="{{ $onWeb === null ? '' : ($onWeb ? 'yes' : 'no') }}">
                        @if($onWeb === null)
                            <span class="text-slate-300 text-[0.75rem]">—</span>
                        @elseif($onWeb)
                            <span class="sw-badge sw-badge-ok">Yes</span>
                        @else
                            <span class="sw-badge sw-badge-out">No</span>
                        @endif
                    </td>
                    <td class="text-center" data-platform-amazon="{{ $onAmazon === null ? '' : ($onAmazon ? 'yes' : 'no') }}">
                        @if($onAmazon === null)
                            <span class="text-slate-300 text-[0.75rem]">—</span>
                        @elseif($onAmazon)
                            <span class="sw-badge sw-badge-ok">Yes</span>
                        @else
                            <span class="sw-badge sw-badge-out">No</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ 16 + count($years) }}" class="px-4 py-8 text-center text-slate-400">
                    No products yet. Add one below.
                </td></tr>
                @endforelse
                </tbody>

                {{-- Subtotal row --}}
                @php
                    $subYearly   = [];
                    foreach ($years as $yr) {
                        $subYearly[$yr] = $category->items->sum(fn($i) => $i->yearly[$yr] ?? 0);
                    }
                    $subAvg     = $category->items->sum(fn($i) => $i->avg_monthly);
                    $subOnHand  = $category->items->sum(fn($i) => $i->stock ? (float)$i->stock->qty_on_hand : 0);
                    $subStockVal= $category->items->sum(fn($i) => $i->stock ? (float)$i->stock->total_cost : 0);
                    $subAllocd  = $category->items->sum(fn($i) => $i->stock ? (float)$i->stock->qty_allocated : 0);
                    $subOnOrder = $category->items->sum(fn($i) => $i->stock ? (float)$i->stock->qty_on_order : 0);
                    $subReq     = $category->items->sum(fn($i) => $i->discontinued ? 0 : $i->required_qty);
                    $subToOrder = $category->items->sum(fn($i) => (float)($i->to_order_qty ?? 0));
                    $subTotal   = $category->items->sum(fn($i) => (float)($i->to_order_qty ?? 0) * (float)($i->unit_price ?? 0));
                @endphp
                <tbody>
                    <tr id="subtotal-row" class="font-bold bg-slate-100 border-t-2 border-slate-200">
                        <td class="text-[0.7rem] text-slate-500 uppercase tracking-wide">Subtotal</td>
                        <td></td>
                        <td></td>
                        @foreach($years as $yr)
                        <td class="sw-num">{{ $subYearly[$yr] > 0 ? number_format($subYearly[$yr], 0) : '—' }}</td>
                        @endforeach
                        <td class="sw-num">{{ $subAvg > 0 ? number_format($subAvg, 1) : '—' }}</td>
                        <td class="sw-num">{{ $subOnHand > 0 ? number_format($subOnHand, 0) : '—' }}</td>
                        <td class="sw-num font-bold">{{ $subStockVal > 0 ? '£' . number_format($subStockVal, 2) : '—' }}</td>
                        <td class="sw-num">{{ $subAllocd > 0 ? number_format($subAllocd, 0) : '—' }}</td>
                        <td class="sw-num">{{ $subOnOrder > 0 ? number_format($subOnOrder, 0) : '—' }}</td>
                        <td class="sw-num">{{ $subReq > 0 ? number_format($subReq, 0) : '—' }}</td>
                        <td class="sw-num sw-sub-toorder">{{ $subToOrder > 0 ? number_format($subToOrder, 0) : '—' }}</td>
                        <td></td>
                        <td class="sw-num sw-sub-total" data-currency="{{ $category->currency ?? '£' }}" data-amount="{{ $subTotal > 0 ? number_format($subTotal, 2, '.', '') : '' }}"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </tbody>

                {{-- Add product row --}}
                <tbody>
                    <tr class="sw-add-row">
                        <td colspan="{{ 16 + count($years) }}" class="px-2.5 py-1.5">
                            <form class="inline-flex gap-2 items-center"
                                onsubmit="addItem(event, this)">
                                <input type="text" name="product_code" placeholder="Product code…"
                                    class="border border-slate-200 rounded-md px-2.5 py-1 text-[0.8rem] text-slate-700 w-40 uppercase"
                                    oninput="this.value=this.value.toUpperCase()" required>
                                <button type="submit"
                                    class="px-3.5 py-1 bg-slate-900 text-white border-none rounded-md text-[0.8rem] font-semibold cursor-pointer hover:bg-slate-700">
                                    + Add
                                </button>
                            </form>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</main>

<script>
const csrfToken   = '{{ csrf_token() }}';
const categoryId  = {{ $category->id }};
const categoryUrl = `/stock-watchlist/categories/${categoryId}`;

// ── Sync ──────────────────────────────────────────────────────────────────────
function runSync() {
    const btn  = document.getElementById('sync-btn');
    const icon = document.getElementById('sync-icon');
    btn.disabled = true;
    icon.style.animation = 'spin 1s linear infinite';
    document.getElementById('sync-status').textContent = 'Syncing…';

    fetch('{{ route("stock-watchlist.sync") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ category_id: categoryId }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) {
            document.getElementById('sync-status').textContent = `Synced ${d.products} products`;
            setTimeout(() => location.reload(), 600);
        } else {
            alert('Sync failed: ' + (d.error || 'Unknown error'));
            icon.style.animation = '';
            btn.disabled = false;
        }
    })
    .catch(() => {
        alert('Sync request failed. Check network.');
        icon.style.animation = '';
        btn.disabled = false;
    });
}

// ── Category fields ───────────────────────────────────────────────────────────
function saveCatField(field, value) {
    fetch(categoryUrl, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ [field]: value }),
    });
}

function saveCatCurrency(sym) {
    saveCatField('currency', sym);
    document.querySelectorAll('.sw-total').forEach(td => {
        td.dataset.currency = sym;
        renderTotal(td);
    });
    const sub = document.querySelector('.sw-sub-total');
    if (sub) { sub.dataset.currency = sym; renderTotal(sub); }
}

// ── Import CSV ────────────────────────────────────────────────────────────────
function importCatItems(input) {
    const file = input.files[0];
    if (!file) return;
    input.value = '';
    const form = new FormData();
    form.append('file', file);
    form.append('_token', csrfToken);
    fetch(`${categoryUrl}/items/import`, {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: form,
    })
    .then(r => r.json())
    .then(d => {
        if (d.ok) { alert(`Import complete: ${d.added} added, ${d.updated} updated, ${d.removed} removed. Page will reload.`); location.reload(); }
        else alert('Import failed: ' + (d.error || 'Unknown error'));
    })
    .catch(() => alert('Import request failed.'));
}

// ── Items ─────────────────────────────────────────────────────────────────────
function addItem(e, form) {
    e.preventDefault();
    const code = form.product_code.value.trim().toUpperCase();
    if (!code) return;
    fetch(`${categoryUrl}/items`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ product_code: code }),
    })
    .then(r => r.json())
    .then(d => {
        if (d.error) { alert(d.error); return; }
        form.product_code.value = '';
        location.reload();
    })
    .catch(() => alert('Failed to add product'));
}

function saveField(itemId, field, value) {
    fetch(`/stock-watchlist/items/${itemId}`, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ [field]: value }),
    });
}

// ── Required → To Order ───────────────────────────────────────────────────────
function copyRequired(cell) {
    const itemId = cell.dataset.itemId;
    const reqQty = parseInt(cell.dataset.reqQty, 10);
    if (!itemId || !reqQty) return;
    const row   = cell.closest('tr');
    const input = row.querySelector('input[data-field="to_order"]');
    if (!input) return;
    input.value = reqQty;
    saveField(itemId, 'to_order_qty', reqQty);
    recalcTotal(row);
    input.classList.add('sw-flash');
    setTimeout(() => input.classList.remove('sw-flash'), 800);
}

function fillAllRequired() {
    const cells = [...document.querySelectorAll('.sw-req-click')].filter(c => !c.closest('tr').classList.contains('sw-disc-row'));
    if (!cells.length) return;
    if (!confirm(`Fill To Order for all ${cells.length} product(s) that need ordering? This will overwrite any existing To Order values.`)) return;
    cells.forEach(cell => copyRequired(cell));
}

// ── Totals ────────────────────────────────────────────────────────────────────
function recalcTotal(row) {
    const toOrder = parseFloat(row.querySelector('[data-field="to_order"]')?.value) || 0;
    const price   = parseFloat(row.querySelector('[data-field="unit_price"]')?.value) || 0;
    const total   = toOrder * price;
    const cell    = row.querySelector('.sw-total');
    if (!cell) return;
    cell.dataset.amount = total > 0 ? total.toFixed(2) : '';
    renderTotal(cell);
    updateSubtotal();
}

function updateSubtotal() {
    const tbody    = document.getElementById('sortable-items');
    const subRow   = document.getElementById('subtotal-row');
    if (!tbody || !subRow) return;
    let toOrderSum = 0, totalSum = 0;
    tbody.querySelectorAll('tr[data-id]').forEach(row => {
        toOrderSum += parseFloat(row.querySelector('[data-field="to_order"]')?.value) || 0;
        totalSum   += parseFloat(row.querySelector('.sw-total')?.dataset.amount) || 0;
    });
    const subToOrder = subRow.querySelector('.sw-sub-toorder');
    const subTotal   = subRow.querySelector('.sw-sub-total');
    if (subToOrder) subToOrder.textContent = toOrderSum > 0 ? toOrderSum.toLocaleString('en-GB', {maximumFractionDigits:0}) : '—';
    if (subTotal) { subTotal.dataset.amount = totalSum > 0 ? totalSum.toFixed(2) : ''; renderTotal(subTotal); }
}

function renderTotal(td) {
    const amt = td.dataset.amount;
    const sym = td.dataset.currency || '£';
    td.textContent = amt ? sym + Number(amt).toLocaleString('en-GB', {minimumFractionDigits:2, maximumFractionDigits:2}) : '—';
}

document.querySelectorAll('.sw-total').forEach(renderTotal);
updateSubtotal();
applyFilters();

// ── Clear To Order (this category only) ──────────────────────────────────────
function clearCatOrders() {
    if (!confirm('Clear all To Order quantities for this category?')) return;
    fetch('{{ route("stock-watchlist.items.clear-orders") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ category_id: categoryId }),
    })
    .then(r => r.json())
    .then(d => {
        if (!d.ok) { alert('Failed to clear orders'); return; }
        document.querySelectorAll('input[data-field="to_order"]').forEach(input => {
            input.value = '';
            recalcTotal(input.closest('tr'));
        });
    });
}

// ── Discontinued ──────────────────────────────────────────────────────────────
function toggleDiscontinued(checkbox, itemId) {
    const isDisc = checkbox.checked;
    const row    = checkbox.closest('tr');
    row.classList.toggle('sw-disc-row', isDisc);
    const badge = row.querySelector('.sw-badge');
    if (badge) {
        if (isDisc) {
            badge.dataset.savedClass = badge.className;
            badge.dataset.savedText  = badge.textContent.trim();
            badge.className   = 'sw-badge sw-badge-disc';
            badge.textContent = 'Discontinued';
        } else if (badge.dataset.savedClass) {
            badge.className   = badge.dataset.savedClass;
            badge.textContent = badge.dataset.savedText;
        }
    }
    saveField(itemId, 'discontinued', isDisc ? 1 : 0);
}


function escHtml(str) {
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Column filters ────────────────────────────────────────────────────────────
function cellText(cell) {
    if (!cell) return '';
    const inp = cell.querySelector('input[type="text"]');
    if (inp) return inp.value;
    return cell.textContent.trim();
}
function cellNum(cell) {
    const t = cellText(cell).replace(/,/g, '').replace('—', '0');
    return parseFloat(t) || 0;
}

// Parse Excel-style numeric filter: =0  >10  >=10  <5  <=100  or plain 42 (exact)
function numMatches(cellVal, raw) {
    const v = raw.trim();
    if (!v) return true;
    const m = v.match(/^([><]=?|=)?(-?\d+(\.\d+)?)$/);
    if (!m) return true; // unparseable — don't filter
    const op  = m[1] || '=';
    const num = parseFloat(m[2]);
    if (op === '>')  return cellVal >  num;
    if (op === '>=') return cellVal >= num;
    if (op === '<')  return cellVal <  num;
    if (op === '<=') return cellVal <= num;
    return cellVal === num; // = or bare number → exact
}

function applyFilters() {
    const filters = [...document.querySelectorAll('.col-filter')].map(el => ({
        col:   el.dataset.col !== undefined ? parseInt(el.dataset.col) : null,
        type:  el.dataset.type,
        value: el.value.trim(),
        el,
    })).filter(f => f.value !== '');

    const rows = document.querySelectorAll('#sortable-items tr[data-id]');
    let visible = 0;
    rows.forEach(row => {
        let show = true;
        for (const f of filters) {
            if (!show) break;
            if (f.type === 'disc') {
                const isDisc = row.classList.contains('sw-disc-row');
                if (f.value === 'active' && isDisc)  { show = false; break; }
                if (f.value === 'disc'   && !isDisc) { show = false; break; }
                continue;
            }
            if (f.type === 'platform') {
                const p = f.el.dataset.platform;
                const td = row.querySelector(`td[data-platform-${p}]`);
                const val = td ? td.getAttribute(`data-platform-${p}`) : '';
                if (val !== f.value) { show = false; break; }
                continue;
            }
            const cell = row.cells[f.col];
            if      (f.type === 'text')   { if (!cellText(cell).toLowerCase().includes(f.value.toLowerCase())) show = false; }
            else if (f.type === 'num')    { if (!numMatches(cellNum(cell), f.value)) show = false; }
            else if (f.type === 'select') { if (!cellText(cell).includes(f.value)) show = false; }
        }
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    const countEl = document.getElementById('sw-filter-count');
    if (countEl) countEl.textContent = filters.length ? `${visible} of ${rows.length} rows shown` : `${rows.length} rows`;
}

function clearFilters() {
    document.querySelectorAll('.col-filter').forEach(el => { el.value = ''; });
    applyFilters();
}

// ── Platform SKU uploads ──────────────────────────────────────────────────────
function uploadPlatformFile(input, platform, countElId) {
    const file = input.files[0];
    if (!file) return;
    input.value = '';
    const form = new FormData();
    form.append('file', file);
    form.append('_token', csrfToken);
    const url = `${categoryUrl}/upload-${platform}`;
    fetch(url, { method: 'POST', headers: { 'Accept': 'application/json' }, body: form })
        .then(r => r.json())
        .then(d => {
            if (d.ok) {
                document.getElementById(countElId).textContent = d.count.toLocaleString() + ' SKUs';
                location.reload();
            } else {
                alert('Upload failed: ' + (d.error || 'Unknown error'));
            }
        })
        .catch(() => alert('Upload request failed.'));
}

// ── Export visible rows to CSV ────────────────────────────────────────────────
function exportTableCsv() {
    const headerRow = document.querySelector('.sw-table thead tr:first-child');
    const headers = [...headerRow.cells].map(th => {
        return th.innerText.replace(/\n/g, ' ').trim();
    });

    const rows = [...document.querySelectorAll('#sortable-items tr[data-id]')]
        .filter(r => r.style.display !== 'none');

    const csvRows = [headers];
    rows.forEach(row => {
        const cols = [...row.cells].map((cell, i) => {
            // Notes input
            const textInput = cell.querySelector('input[type="text"]');
            if (textInput) return textInput.value;
            // To Order / Price inputs
            const numInput = cell.querySelector('input[type="number"]');
            if (numInput) return numInput.value;
            // Disc checkbox
            const chk = cell.querySelector('input[type="checkbox"]');
            if (chk) return chk.checked ? 'Yes' : 'No';
            // Badge (Status)
            const badge = cell.querySelector('.sw-badge');
            if (badge) return badge.textContent.trim();
            // General text — strip currency symbol for numeric columns
            return cell.textContent.trim().replace(/^[£$€]/, '').replace(/,/g, '').replace('—', '');
        });
        csvRows.push(cols);
    });

    const csv = csvRows.map(r => r.map(v => {
        const s = String(v).replace(/"/g, '""');
        return /[,"\n]/.test(s) ? `"${s}"` : s;
    }).join(',')).join('\r\n');

    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href     = url;
    a.download = '{{ Str::slug($category->name) }}-stock-watchlist.csv';
    a.click();
    URL.revokeObjectURL(url);
}
</script>


<style>@keyframes spin { to { transform: rotate(360deg); } }</style>
</x-layout>
