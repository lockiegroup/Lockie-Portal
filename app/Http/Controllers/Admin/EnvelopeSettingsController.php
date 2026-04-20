<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EnvelopeDesign;
use App\Models\EnvelopeVerse;
use Illuminate\Http\Request;

class EnvelopeSettingsController extends Controller
{
    public function index()
    {
        $verses  = EnvelopeVerse::orderBy('sort_order')->get();
        $designs = EnvelopeDesign::orderBy('name')->get();

        return view('admin.envelope-settings.index', compact('verses', 'designs'));
    }

    public function storeVerse(Request $r)
    {
        $r->validate([
            'label'      => 'required|string|max:10|unique:envelope_verses,label',
            'lines'      => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);

        $lines = [];
        $raw   = $r->input('lines', []);
        for ($i = 0; $i < 8; $i++) {
            $lines[] = trim($raw[$i] ?? '');
        }

        EnvelopeVerse::create([
            'label'      => trim($r->label),
            'lines'      => $lines,
            'sort_order' => (int) ($r->sort_order ?? 0),
        ]);

        return back()->with('success', 'Verse added successfully.');
    }

    public function updateVerse(Request $r, EnvelopeVerse $verse)
    {
        $r->validate([
            'label'      => 'required|string|max:10|unique:envelope_verses,label,' . $verse->id,
            'lines'      => 'nullable|array',
            'sort_order' => 'nullable|integer',
        ]);

        $lines = [];
        $raw   = $r->input('lines', []);
        for ($i = 0; $i < 8; $i++) {
            $lines[] = trim($raw[$i] ?? '');
        }

        $verse->update([
            'label'      => trim($r->label),
            'lines'      => $lines,
            'sort_order' => (int) ($r->sort_order ?? 0),
        ]);

        return back()->with('success', 'Verse updated successfully.');
    }

    public function destroyVerse(EnvelopeVerse $verse)
    {
        $verse->delete();

        return back()->with('success', 'Verse deleted.');
    }

    public function storeDesign(Request $r)
    {
        $r->validate([
            'name' => 'required|string|max:100',
            'path' => 'required|string|max:500',
        ]);

        EnvelopeDesign::create([
            'name' => trim($r->name),
            'path' => trim($r->path),
        ]);

        return back()->with('success', 'Design added successfully.');
    }

    public function updateDesign(Request $r, EnvelopeDesign $design)
    {
        $r->validate([
            'name' => 'required|string|max:100',
            'path' => 'required|string|max:500',
        ]);

        $design->update([
            'name' => trim($r->name),
            'path' => trim($r->path),
        ]);

        return back()->with('success', 'Design updated successfully.');
    }

    public function destroyDesign(EnvelopeDesign $design)
    {
        $design->delete();

        return back()->with('success', 'Design deleted.');
    }
}
