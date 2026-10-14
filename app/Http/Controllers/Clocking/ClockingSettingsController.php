<?php

namespace App\Http\Controllers\Clocking;

use App\Http\Controllers\Controller;
use App\Models\ClockingAlertRecipient;
use App\Models\ClockingCategory;
use App\Models\ClockingSettingRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClockingSettingsController extends Controller
{
    public function index(): View
    {
        $categories  = ClockingCategory::with('alertRecipients')->orderBy('name')->get();
        $globalRecipients = ClockingAlertRecipient::whereNull('category_id')->orderBy('email')->get();
        $graceMins   = (int) (ClockingSettingRecord::where('key', 'alert_grace_minutes')->value('value') ?? 15);

        return view('clocking.settings', compact('categories', 'globalRecipients', 'graceMins'));
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function storeCategory(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:80',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);
        $cat = ClockingCategory::create(['name' => $data['name'], 'color' => $data['color'] ?? '#6366f1']);
        return response()->json(['ok' => true, 'id' => $cat->id, 'name' => $cat->name, 'color' => $cat->color]);
    }

    public function updateCategory(Request $request, ClockingCategory $category): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:80',
            'color' => 'nullable|string|regex:/^#[0-9a-fA-F]{6}$/',
        ]);
        $category->update(['name' => $data['name'], 'color' => $data['color'] ?? $category->color]);
        return response()->json(['ok' => true]);
    }

    public function destroyCategory(ClockingCategory $category): JsonResponse
    {
        $category->delete();
        return response()->json(['ok' => true]);
    }

    // ── Alert recipients ──────────────────────────────────────────────────────

    public function storeRecipient(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:clocking_categories,id',
            'email'       => 'required|email|max:200',
            'label'       => 'nullable|string|max:100',
        ]);
        $r = ClockingAlertRecipient::create($data);
        return response()->json(['ok' => true, 'id' => $r->id]);
    }

    public function destroyRecipient(ClockingAlertRecipient $recipient): JsonResponse
    {
        $recipient->delete();
        return response()->json(['ok' => true]);
    }

    // ── Settings ──────────────────────────────────────────────────────────────

    public function updateSettings(Request $request): JsonResponse
    {
        $data = $request->validate([
            'alert_grace_minutes' => 'required|integer|min:0|max:120',
        ]);
        ClockingSettingRecord::updateOrCreate(
            ['key' => 'alert_grace_minutes'],
            ['value' => $data['alert_grace_minutes']]
        );
        return response()->json(['ok' => true]);
    }
}
