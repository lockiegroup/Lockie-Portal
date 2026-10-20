<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function __invoke(Request $request)
    {
        $userId   = $request->input('user_id');
        $category = $request->input('category');
        $search   = trim($request->input('search', ''));
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $logs = ActivityLog::with('user')
            ->when($userId,   fn($q) => $q->where('user_id', $userId))
            ->when($category, fn($q) => $q->where('action', 'like', $category . '.%'))
            ->when($search,   fn($q) => $q->where('description', 'like', '%' . $search . '%'))
            ->when($dateFrom, fn($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->orderByDesc('created_at')
            ->paginate(75)
            ->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);

        $categories = [
            'auth'             => 'Login / Logout',
            'clocking'         => 'Clocking',
            'crm'              => 'CRM',
            'envelope'         => 'Church Envelopes',
            'print'            => 'Print Schedule',
            'policy'           => 'Policies',
            'cashflow'         => 'Cash Flow',
            'racking'          => 'Racking',
            'users'            => 'User Management',
            'key_accounts'     => 'Key Accounts',
            'key_actions'      => 'Key Actions',
            'imports'          => 'Imports',
            'reminders'        => 'Reminders',
            'stock_forecast'   => 'Stock Forecast',
            'supplier_settings'=> 'Supplier Settings',
        ];

        return view('admin.activity-log.index', compact(
            'logs', 'users', 'categories',
            'userId', 'category', 'search', 'dateFrom', 'dateTo'
        ));
    }
}
