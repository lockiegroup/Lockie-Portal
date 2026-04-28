<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\KeyAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KeyAccountAdminController extends Controller
{
    public function index(): View
    {
        $accounts = KeyAccount::with('user')->orderBy('name')->get();
        $users    = User::where('is_active', true)->orderBy('name')->get();

        return view('admin.key-accounts.index', compact('accounts', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_code' => ['required', 'string', 'max:50', 'unique:key_accounts,account_code'],
            'name'         => ['required', 'string', 'max:200'],
            'type'         => ['required', 'in:key,growth'],
            'user_id'      => ['nullable', 'exists:users,id'],
        ]);

        KeyAccount::create($data);
        ActivityLog::record('key_accounts.created', "Created account {$data['account_code']}");

        return back()->with('success', "Account {$data['account_code']} created.");
    }

    public function update(Request $request, KeyAccount $keyAccount): RedirectResponse
    {
        $data = $request->validate([
            'account_code' => ['required', 'string', 'max:50', 'unique:key_accounts,account_code,' . $keyAccount->id],
            'name'         => ['required', 'string', 'max:200'],
            'type'         => ['required', 'in:key,growth'],
            'user_id'      => ['nullable', 'exists:users,id'],
        ]);

        $keyAccount->update($data);
        ActivityLog::record('key_accounts.updated', "Updated account {$keyAccount->account_code}");

        return back()->with('success', 'Account updated.');
    }

    public function destroy(KeyAccount $keyAccount): RedirectResponse
    {
        $code = $keyAccount->account_code;
        $keyAccount->delete();
        ActivityLog::record('key_accounts.deleted', "Deleted account {$code}");

        return back()->with('success', "Account {$code} deleted.");
    }
}
