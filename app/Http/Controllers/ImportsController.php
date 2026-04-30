<?php

namespace App\Http\Controllers;

use App\Models\StockWatchlistSubstitution;
use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class ImportsController extends Controller
{
    public function index(): View
    {
        $user    = auth()->user();
        $doKA    = $user->hasModule('key_accounts') || $user->can('key_accounts_admin');
        $doStock = $user->can('stock_ordering');

        if (!$doKA && !$doStock) {
            abort(403);
        }

        $substitutions = $doStock ? StockWatchlistSubstitution::orderBy('id')->get() : collect();

        $salesFrom = $salesTo = null;
        $range = DB::table('sales_lines')
            ->selectRaw('MIN(COALESCE(completed_date, order_date)) as min_d, MAX(COALESCE(completed_date, order_date)) as max_d')
            ->first();
        if ($range && $range->min_d) {
            $salesFrom = Carbon::parse($range->min_d)->format('jS M Y');
            $salesTo   = Carbon::parse($range->max_d)->format('jS M Y');
        }

        $lastImport = \App\Models\ActivityLog::whereIn('action', ['imports.sales', 'imports.sales.queued', 'imports.sales.error'])
            ->latest('created_at')
            ->first();

        return view('imports.index', compact('doKA', 'doStock', 'substitutions', 'salesFrom', 'salesTo', 'lastImport'));
    }

    public function storeSubstitution(Request $request): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->can('stock_ordering')) abort(403);

        $data = $request->validate([
            'find'    => ['required', 'string', 'max:100'],
            'replace' => ['required', 'string', 'max:100'],
        ]);

        StockWatchlistSubstitution::create([
            'find'    => strtoupper(trim($data['find'])),
            'replace' => strtoupper(trim($data['replace'])),
        ]);

        ActivityLog::record('imports.substitution_added', "Added substitution rule: {$data['find']} → {$data['replace']}");

        return back()->with('success', 'Substitution rule added.');
    }

    public function destroySubstitution(StockWatchlistSubstitution $substitution): RedirectResponse
    {
        $user = auth()->user();
        if (!$user->can('stock_ordering')) abort(403);

        $substitution->delete();

        return back()->with('success', 'Substitution rule removed.');
    }

    public function storeSales(Request $request): RedirectResponse
    {
        $user    = auth()->user();
        $doKA    = $user->hasModule('key_accounts') || $user->can('key_accounts_admin');
        $doStock = $user->can('stock_ordering');

        if (!$doKA && !$doStock) {
            abort(403);
        }

        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv|max:20480']);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        try {
            $dir = storage_path('app/imports');
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $savedPath = $dir . '/sales-import-' . time() . '.' . $ext;
            $file->move(dirname($savedPath), basename($savedPath));

            $phpBin = file_exists('/usr/local/php84/bin/php-cli') ? '/usr/local/php84/bin/php-cli' : 'php';
            $artisan = base_path('artisan');
            exec('nohup ' . $phpBin . ' ' . escapeshellarg($artisan) . ' imports:process-sales ' . escapeshellarg($savedPath) . ' > /dev/null 2>&1 &');

            ActivityLog::record('imports.sales.queued', 'Sales import queued for background processing');

            return back()->with('success', 'Import started — your file is being processed in the background. Data will be updated within a minute or two. Refresh the page to see the updated date range once complete.');
        } catch (\Throwable $e) {
            return back()->withErrors(['file' => 'Could not start import: ' . $e->getMessage()]);
        }
    }

}
