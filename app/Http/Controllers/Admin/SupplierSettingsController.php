<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForecastProduct;
use App\Models\SupplierSetting;
use Illuminate\Http\Request;

class SupplierSettingsController extends Controller
{
    public function index()
    {
        $knownSuppliers  = ForecastProduct::whereNotNull('supplier_name')
            ->distinct()->orderBy('supplier_name')
            ->pluck('supplier_name');

        $savedSettings   = SupplierSetting::pluck('lead_time_weeks', 'supplier_name')->toArray();

        $suppliers = $knownSuppliers->map(fn($name) => [
            'name'            => $name,
            'lead_time_weeks' => $savedSettings[$name] ?? 4,
        ]);

        return view('admin.supplier-settings.index', compact('suppliers'));
    }

    public function update(Request $request)
    {
        $leadTimes = $request->input('lead_times', []);

        foreach ($leadTimes as $supplierName => $weeks) {
            $weeks = (int) $weeks;
            if ($weeks < 1) $weeks = 1;
            if ($weeks > 52) $weeks = 52;
            SupplierSetting::updateOrCreate(
                ['supplier_name' => $supplierName],
                ['lead_time_weeks' => $weeks]
            );
        }

        \App\Models\ActivityLog::record('supplier_settings.update', 'Updated supplier lead times');

        return redirect()->route('admin.supplier-settings.index')
            ->with('success', 'Lead times saved.');
    }
}
