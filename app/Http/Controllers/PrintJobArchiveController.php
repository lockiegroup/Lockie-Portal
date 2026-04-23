<?php

namespace App\Http\Controllers;

use App\Models\PrintJob;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrintJobArchiveController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->input('q', ''));

        $jobs = PrintJob::whereNotNull('archived_at')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('order_number',         'like', '%' . $search . '%')
                      ->orWhere('customer_name',       'like', '%' . $search . '%')
                      ->orWhere('customer_ref',        'like', '%' . $search . '%')
                      ->orWhere('product_code',        'like', '%' . $search . '%')
                      ->orWhere('product_description', 'like', '%' . $search . '%')
                      ->orWhere('line_comment',        'like', '%' . $search . '%');
                });
            })
            ->orderByRaw('COALESCE(despatched_at, order_date) DESC')
            ->paginate(30)
            ->withQueryString();

        return view('print-schedule.archive', compact('jobs', 'search'));
    }
}
