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

        $logs = ActivityLog::with('user')
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->when($category, fn($q) => $q->where('action', 'like', $category . '.%'))
            ->orderByDesc('created_at')
            ->paginate(75)
            ->withQueryString();

        $users = User::orderBy('name')->get(['id', 'name']);

        $categories = [
            'auth'         => 'Login / Logout',
            'envelope'     => 'Church Envelopes',
            'print'        => 'Print Schedule',
            'policy'       => 'Policies',
            'cashflow'     => 'Cash Flow',
            'users'        => 'User Management',
            'key_accounts' => 'Key Accounts',
            'imports'      => 'Imports',
        ];

        return view('admin.activity-log.index', compact('logs', 'users', 'categories', 'userId', 'category'));
    }
}
