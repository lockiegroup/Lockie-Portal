<?php

namespace App\Http\Controllers;

use App\Models\OutsideStorageItem;
use App\Models\RackingItem;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;

class RackingController extends Controller
{
    // ── Main Racking ──────────────────────────────────────────────────────────

    public function index(Request $request): View
    {
        $query = RackingItem::orderByRaw("SUBSTR(bay,1,1), CAST(SUBSTR(bay,2) AS UNSIGNED), sort_order, id");

        if ($request->filled('bay'))      $query->where('bay', $request->bay);
        if ($request->filled('division')) $query->where('division', $request->division);
        if ($request->filled('q'))        $query->where('description', 'like', '%'.$request->q.'%');

        $items     = $query->get()->groupBy('bay');
        $divisions = RackingItem::distinct()->orderBy('division')->pluck('division')->filter()->values();
        $bays      = RackingItem::bays();

        $unusableCount       = RackingItem::where('is_unusable', true)->count();
        $forOutsideCount     = RackingItem::where('for_outside_storage', true)->count();
        $availableSpaces     = (int) DB::table('app_settings')->where('key', 'racking_available_spaces')->value('value') ?: 0;
        $outsideStorageCount = OutsideStorageItem::count();

        return view('racking.index', compact(
            'items', 'divisions', 'bays',
            'unusableCount', 'forOutsideCount', 'availableSpaces', 'outsideStorageCount'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bay'                  => 'required|string|max:3',
            'division'             => 'nullable|string|max:100',
            'description'          => 'nullable|string|max:500',
            'pallet_ref'           => 'nullable|string|max:100',
            'quantity'             => 'nullable|string|max:100',
            'date_stored'          => 'nullable|date',
            'is_unusable'          => 'boolean',
            'for_outside_storage'  => 'boolean',
            'notes'                => 'nullable|string|max:500',
        ]);
        $data['is_unusable']         = $request->boolean('is_unusable');
        $data['for_outside_storage'] = $request->boolean('for_outside_storage');
        $data['sort_order']          = RackingItem::where('bay', $data['bay'])->max('sort_order') + 1;

        RackingItem::create($data);

        return redirect()->route('racking.index', $request->only(['bay', 'division', 'q']))
            ->with('success', 'Item added.');
    }

    public function update(Request $request, RackingItem $rackingItem): RedirectResponse
    {
        $data = $request->validate([
            'bay'                  => 'required|string|max:3',
            'division'             => 'nullable|string|max:100',
            'description'          => 'nullable|string|max:500',
            'pallet_ref'           => 'nullable|string|max:100',
            'quantity'             => 'nullable|string|max:100',
            'date_stored'          => 'nullable|date',
            'is_unusable'          => 'boolean',
            'for_outside_storage'  => 'boolean',
            'notes'                => 'nullable|string|max:500',
        ]);
        $data['is_unusable']         = $request->boolean('is_unusable');
        $data['for_outside_storage'] = $request->boolean('for_outside_storage');

        $rackingItem->update($data);

        return redirect()->route('racking.index', $request->only(['bay', 'division', 'q']))
            ->with('success', 'Item updated.');
    }

    public function destroy(Request $request, RackingItem $rackingItem): RedirectResponse
    {
        $rackingItem->delete();

        return redirect()->route('racking.index', $request->only(['bay', 'division', 'q']))
            ->with('success', 'Item removed.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $spaces = max(0, (int) $request->input('available_spaces', 0));
        DB::table('app_settings')->updateOrInsert(
            ['key' => 'racking_available_spaces'],
            ['value' => $spaces, 'updated_at' => now(), 'created_at' => now()]
        );
        return redirect()->route('racking.index')->with('success', 'Available spaces updated.');
    }

    // ── Outside Storage ───────────────────────────────────────────────────────

    public function outside(): View
    {
        $items = OutsideStorageItem::orderByDesc('storage_date')->orderByDesc('id')->get();
        return view('racking.outside', compact('items'));
    }

    public function storeOutside(Request $request): RedirectResponse
    {
        OutsideStorageItem::create($request->validate([
            'storage_date' => 'nullable|date',
            'colour'       => 'nullable|string|max:100',
            'quantity'     => 'nullable|string|max:100',
            'ref'          => 'nullable|string|max:50',
            'year'         => 'nullable|integer|min:2000|max:2100',
            'return_date'  => 'nullable|date',
            'notes'        => 'nullable|string|max:500',
        ]));
        return redirect()->route('racking.outside')->with('success', 'Item added to outside storage.');
    }

    public function updateOutside(Request $request, OutsideStorageItem $outsideStorageItem): RedirectResponse
    {
        $outsideStorageItem->update($request->validate([
            'storage_date' => 'nullable|date',
            'colour'       => 'nullable|string|max:100',
            'quantity'     => 'nullable|string|max:100',
            'ref'          => 'nullable|string|max:50',
            'year'         => 'nullable|integer|min:2000|max:2100',
            'return_date'  => 'nullable|date',
            'notes'        => 'nullable|string|max:500',
        ]));
        return redirect()->route('racking.outside')->with('success', 'Outside storage item updated.');
    }

    public function destroyOutside(Request $request, OutsideStorageItem $outsideStorageItem): RedirectResponse
    {
        $outsideStorageItem->delete();
        return redirect()->route('racking.outside')->with('success', 'Item removed.');
    }

    // ── Stock Movements ───────────────────────────────────────────────────────

    public function movements(): View
    {
        $movements = StockMovement::orderByDesc('moved_at')->orderByDesc('id')->get();
        $bays      = RackingItem::bays();
        return view('racking.movements', compact('movements', 'bays'));
    }

    public function storeMovement(Request $request): RedirectResponse
    {
        StockMovement::create($request->validate([
            'moved_at'      => 'required|date',
            'description'   => 'required|string|max:500',
            'colour'        => 'nullable|string|max:100',
            'quantity'      => 'nullable|string|max:100',
            'from_location' => 'nullable|string|max:50',
            'to_location'   => 'nullable|string|max:50',
            'notes'         => 'nullable|string|max:500',
        ]));
        return redirect()->route('racking.movements')->with('success', 'Movement recorded.');
    }

    public function destroyMovement(Request $request, StockMovement $stockMovement): RedirectResponse
    {
        $stockMovement->delete();
        return redirect()->route('racking.movements')->with('success', 'Movement deleted.');
    }

    // ── XLSX Import ───────────────────────────────────────────────────────────

    public function import(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());

        // ── Main Racking sheet ─────────────────────────────────────────────
        $sheet = $spreadsheet->getSheetByName('Main Racking') ?? $spreadsheet->getSheet(0);
        $rows  = $sheet->toArray(null, true, true, false);

        // Row 0 = title, Row 1 = headers, data starts row 2
        $imported = 0;
        foreach (array_slice($rows, 2) as $row) {
            $bay  = trim((string) ($row[0] ?? ''));
            $desc = trim((string) ($row[2] ?? ''));
            if ($bay === '' && $desc === '') continue;
            if ($bay === '') continue;

            $dateRaw   = $row[5] ?? null;
            $dateStore = null;
            if ($dateRaw !== null && $dateRaw !== '' && $dateRaw !== '-') {
                if (is_numeric($dateRaw)) {
                    try { $dateStore = XlsDate::excelToDateTimeObject((float)$dateRaw)->format('Y-m-d'); } catch (\Throwable) {}
                } else {
                    try { $dateStore = \Carbon\Carbon::parse($dateRaw)->format('Y-m-d'); } catch (\Throwable) {}
                }
            }

            $isUnusable = str_contains(strtolower($desc), 'unusable');
            $qty        = trim((string) ($row[4] ?? ''));
            $qty        = ($qty === '-') ? null : ($qty ?: null);

            RackingItem::create([
                'bay'          => strtoupper($bay),
                'division'     => trim((string) ($row[1] ?? '')) ?: null,
                'description'  => $desc ?: null,
                'pallet_ref'   => trim((string) ($row[3] ?? '')) ?: null,
                'quantity'     => $qty,
                'date_stored'  => $dateStore,
                'is_unusable'  => $isUnusable,
                'sort_order'   => $imported,
            ]);
            $imported++;
        }

        // ── Outside Storage sheet ──────────────────────────────────────────
        try {
            $osSheet = $spreadsheet->getSheetByName('Outside Storage') ?? $spreadsheet->getSheet(1);
            $osRows  = $osSheet->toArray(null, true, true, false);
            $osCount = 0;
            foreach (array_slice($osRows, 1) as $row) {
                $colour = trim((string) ($row[1] ?? ''));
                if ($colour === '') continue;

                $storeDateRaw = $row[0] ?? null;
                $storeDate    = null;
                if ($storeDateRaw && is_numeric($storeDateRaw)) {
                    try { $storeDate = XlsDate::excelToDateTimeObject((float)$storeDateRaw)->format('Y-m-d'); } catch (\Throwable) {}
                } elseif ($storeDateRaw) {
                    try { $storeDate = \Carbon\Carbon::parse($storeDateRaw)->format('Y-m-d'); } catch (\Throwable) {}
                }

                $qty = trim((string) ($row[2] ?? ''));
                OutsideStorageItem::create([
                    'storage_date' => $storeDate,
                    'colour'       => $colour,
                    'quantity'     => $qty ?: null,
                    'ref'          => trim((string) ($row[3] ?? '')) ?: null,
                    'year'         => is_numeric($row[4] ?? '') ? (int)$row[4] : null,
                    'return_date'  => null,
                ]);
                $osCount++;
            }
        } catch (\Throwable) {}

        return redirect()->route('racking.index')
            ->with('success', "Import complete — {$imported} racking items imported.");
    }
}
