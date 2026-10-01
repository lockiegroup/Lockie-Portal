<?php

namespace App\Http\Controllers;

use App\Models\OutsideStorageItem;
use App\Models\RackingItem;
use App\Models\StockMovement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;

class RackingController extends Controller
{
    private function mover(): string
    {
        $user = Auth::user();
        return $user ? ($user->name ?? $user->email) : 'Unknown';
    }

    // ── Main Racking ──────────────────────────────────────────────────────────

    public function index(): View
    {
        $bays      = RackingItem::bays();
        $slots     = RackingItem::SLOTS_PER_BAY;
        $allItems  = RackingItem::orderBy('slot_number')->get()->groupBy('bay');
        $divisions = RackingItem::distinct()->orderBy('division')->pluck('division')->filter()->values();

        // Build a full grid: bay => [slot1 => item|null, slot2 => item|null, ...]
        $grid = [];
        foreach ($bays as $bay) {
            $bySlot = ($allItems[$bay] ?? collect())->keyBy('slot_number');
            for ($s = 1; $s <= $slots; $s++) {
                $grid[$bay][$s] = $bySlot[$s] ?? null;
            }
        }

        $totalSlots      = count($bays) * $slots;
        $filledCount     = RackingItem::whereNotNull('description')->count();
        $unusableCount   = RackingItem::where('is_unusable', true)->count();
        $forOutsideCount = RackingItem::where('for_outside_storage', true)->count();
        $emptyCount      = $totalSlots - RackingItem::count();
        $outsideCount    = OutsideStorageItem::count();

        return view('racking.index', compact(
            'grid', 'bays', 'slots', 'divisions',
            'filledCount', 'unusableCount', 'forOutsideCount', 'emptyCount', 'outsideCount'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'bay'                  => 'required|string|max:3',
            'slot_number'          => 'required|integer|min:1|max:10',
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
        $data['sort_order']          = $data['slot_number'];

        $item = RackingItem::create($data);

        if ($item->description) {
            try {
                StockMovement::create([
                    'moved_at'      => now(),
                    'description'   => $item->description,
                    'quantity'      => $item->quantity,
                    'from_location' => null,
                    'to_location'   => $item->bay . '-' . $item->slot_number,
                    'notes'         => 'Slot filled',
                    'moved_by'      => $this->mover(),
                    'action_type'   => 'filled',
                ]);
            } catch (\Throwable) {}
        }

        return redirect()->route('racking.index')->with('success', 'Slot filled.');
    }

    public function update(Request $request, RackingItem $rackingItem): RedirectResponse
    {
        $data = $request->validate([
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
        return redirect()->route('racking.index')->with('success', 'Slot updated.');
    }

    public function destroy(Request $request, RackingItem $rackingItem): RedirectResponse
    {
        if ($rackingItem->description) {
            try {
                StockMovement::create([
                    'moved_at'      => now(),
                    'description'   => $rackingItem->description,
                    'quantity'      => $rackingItem->quantity,
                    'from_location' => $rackingItem->bay . '-' . $rackingItem->slot_number,
                    'to_location'   => null,
                    'notes'         => 'Slot cleared',
                    'moved_by'      => $this->mover(),
                    'action_type'   => 'cleared',
                ]);
            } catch (\Throwable) {}
        }

        $rackingItem->delete();
        return redirect()->route('racking.index')->with('success', 'Slot cleared.');
    }

    // ── Move Actions ──────────────────────────────────────────────────────────

    public function moveToOutside(Request $request, RackingItem $rackingItem): RedirectResponse
    {
        $slotLabel = $rackingItem->bay . '-' . $rackingItem->slot_number;

        OutsideStorageItem::create([
            'storage_date' => $rackingItem->date_stored ?? now()->toDateString(),
            'colour'       => $rackingItem->description,
            'quantity'     => $rackingItem->quantity,
            'ref'          => $rackingItem->pallet_ref,
            'notes'        => $rackingItem->notes,
        ]);

        try {
            StockMovement::create([
                'moved_at'      => now(),
                'description'   => $rackingItem->description,
                'quantity'      => $rackingItem->quantity,
                'from_location' => $slotLabel,
                'to_location'   => 'Outside Storage',
                'notes'         => 'Moved to outside storage',
                'moved_by'      => $this->mover(),
                'action_type'   => 'moved-outside',
            ]);
        } catch (\Throwable) {}

        $rackingItem->delete();

        return redirect()->route('racking.index')->with('success', 'Moved to outside storage and logged.');
    }

    public function moveToRack(Request $request, OutsideStorageItem $outsideStorageItem): RedirectResponse
    {
        $data = $request->validate([
            'bay'         => 'required|string|max:3',
            'slot_number' => 'required|integer|min:1|max:10',
        ]);

        $occupied = RackingItem::where('bay', $data['bay'])
            ->where('slot_number', $data['slot_number'])
            ->exists();

        if ($occupied) {
            return redirect()->route('racking.outside')
                ->with('error', 'Slot ' . $data['bay'] . '-' . $data['slot_number'] . ' is already occupied. Choose a different slot.');
        }

        $slotLabel = $data['bay'] . '-' . $data['slot_number'];

        RackingItem::create([
            'bay'         => $data['bay'],
            'slot_number' => $data['slot_number'],
            'sort_order'  => $data['slot_number'],
            'description' => $outsideStorageItem->colour,
            'quantity'    => $outsideStorageItem->quantity,
            'pallet_ref'  => $outsideStorageItem->ref,
            'date_stored' => $outsideStorageItem->storage_date,
            'notes'       => $outsideStorageItem->notes,
        ]);

        try {
            StockMovement::create([
                'moved_at'      => now(),
                'description'   => $outsideStorageItem->colour,
                'quantity'      => $outsideStorageItem->quantity,
                'from_location' => 'Outside Storage',
                'to_location'   => $slotLabel,
                'notes'         => 'Moved from outside storage',
                'moved_by'      => $this->mover(),
                'action_type'   => 'moved-to-rack',
            ]);
        } catch (\Throwable) {}

        $outsideStorageItem->delete();

        return redirect()->route('racking.outside')->with('success', 'Moved to racking slot ' . $slotLabel . ' and logged.');
    }

    public function move(Request $request, RackingItem $rackingItem): RedirectResponse
    {
        $data = $request->validate([
            'to_bay'         => 'required|string|max:3',
            'to_slot_number' => 'required|integer|min:1|max:10',
        ]);

        $occupied = RackingItem::where('bay', $data['to_bay'])
            ->where('slot_number', $data['to_slot_number'])
            ->where('id', '!=', $rackingItem->id)
            ->exists();

        if ($occupied) {
            return redirect()->route('racking.index')
                ->with('error', 'Slot ' . $data['to_bay'] . '-' . $data['to_slot_number'] . ' is already occupied. Choose a different slot.');
        }

        $fromLabel = $rackingItem->bay . '-' . $rackingItem->slot_number;
        $toLabel   = $data['to_bay'] . '-' . $data['to_slot_number'];

        $rackingItem->update([
            'bay'         => $data['to_bay'],
            'slot_number' => $data['to_slot_number'],
            'sort_order'  => $data['to_slot_number'],
        ]);

        try {
            StockMovement::create([
                'moved_at'      => now(),
                'description'   => $rackingItem->description,
                'quantity'      => $rackingItem->quantity,
                'from_location' => $fromLabel,
                'to_location'   => $toLabel,
                'notes'         => 'Moved within racking',
                'moved_by'      => $this->mover(),
                'action_type'   => 'moved',
            ]);
        } catch (\Throwable) {}

        return redirect()->route('racking.index')->with('success', 'Moved from ' . $fromLabel . ' to ' . $toLabel . '.');
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        return redirect()->route('racking.index');
    }

    // ── Outside Storage ───────────────────────────────────────────────────────

    public function outside(): View
    {
        $items = OutsideStorageItem::orderByDesc('storage_date')->orderByDesc('id')->get();
        return view('racking.outside', compact('items'));
    }

    public function storeOutside(Request $request): RedirectResponse
    {
        $item = OutsideStorageItem::create($request->validate([
            'storage_date' => 'nullable|date',
            'colour'       => 'nullable|string|max:100',
            'quantity'     => 'nullable|string|max:100',
            'ref'          => 'nullable|string|max:50',
            'year'         => 'nullable|integer|min:2000|max:2100',
            'return_date'  => 'nullable|date',
            'notes'        => 'nullable|string|max:500',
        ]));

        if ($item->colour) {
            try {
                StockMovement::create([
                    'moved_at'      => now(),
                    'description'   => $item->colour,
                    'quantity'      => $item->quantity,
                    'from_location' => null,
                    'to_location'   => 'Outside Storage',
                    'notes'         => 'Added to outside storage',
                    'moved_by'      => $this->mover(),
                    'action_type'   => 'outside-added',
                ]);
            } catch (\Throwable) {}
        }

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
        if ($outsideStorageItem->colour) {
            try {
                StockMovement::create([
                    'moved_at'      => now(),
                    'description'   => $outsideStorageItem->colour,
                    'quantity'      => $outsideStorageItem->quantity,
                    'from_location' => 'Outside Storage',
                    'to_location'   => null,
                    'notes'         => 'Removed from outside storage',
                    'moved_by'      => $this->mover(),
                    'action_type'   => 'outside-removed',
                ]);
            } catch (\Throwable) {}
        }

        $outsideStorageItem->delete();
        return redirect()->route('racking.outside')->with('success', 'Item removed.');
    }

    // ── Stock Movements ───────────────────────────────────────────────────────

    public function movements(): View
    {
        $movements = StockMovement::orderByDesc('created_at')->orderByDesc('id')->get();
        return view('racking.movements', compact('movements'));
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

            // Slot number within this bay (1-based)
            static $baySlotCount = [];
            $bayKey = strtoupper($bay);
            $baySlotCount[$bayKey] = ($baySlotCount[$bayKey] ?? 0) + 1;
            $slotNum = $baySlotCount[$bayKey];

            RackingItem::create([
                'bay'          => $bayKey,
                'slot_number'  => $slotNum,
                'division'     => trim((string) ($row[1] ?? '')) ?: null,
                'description'  => $desc ?: null,
                'pallet_ref'   => trim((string) ($row[3] ?? '')) ?: null,
                'quantity'     => $qty,
                'date_stored'  => $dateStore,
                'is_unusable'  => $isUnusable,
                'sort_order'   => $slotNum,
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
