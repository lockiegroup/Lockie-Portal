<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintJobArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $search    = trim($request->input('q', ''));
        $dateFrom  = $request->input('date_from', '');
        $dateTo    = $request->input('date_to', '');
        $machine   = $request->input('machine', '');

        $jobs = PrintJob::whereNotNull('archived_at')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_number',         'like', '%' . $search . '%')
                      ->orWhere('customer_name',       'like', '%' . $search . '%')
                      ->orWhere('customer_ref',        'like', '%' . $search . '%')
                      ->orWhere('product_code',        'like', '%' . $search . '%')
                      ->orWhere('product_description', 'like', '%' . $search . '%')
                      ->orWhere('line_comment',        'like', '%' . $search . '%')
                      ->orWhere('delivery_address',    'like', '%' . $search . '%')
                      ->orWhere('delivery_city',       'like', '%' . $search . '%')
                      ->orWhere('delivery_postcode',   'like', '%' . $search . '%');
                });
            })
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('archived_at', '>=', $dateFrom))
            ->when($dateTo   !== '', fn ($q) => $q->whereDate('archived_at', '<=', $dateTo))
            ->when($machine  !== '', fn ($q) => $q->whereHas('runs', fn ($r) => $r->where('machine', $machine)))
            ->orderByRaw('CASE WHEN archive_reason = "deleted" THEN order_date ELSE archived_at END DESC')
            ->paginate(30)
            ->withQueryString();

        $machines = PrintJob::MACHINES;

        return view('print-schedule.archive', compact('jobs', 'search', 'dateFrom', 'dateTo', 'machine', 'machines'));
    }
}
