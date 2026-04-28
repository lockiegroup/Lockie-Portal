<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\KeyAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KeyAccountAdminController extends Controller
{
    public function index(): View
    {
        $accounts = KeyAccount::with('user')->whereNotNull('user_id')->orderBy('name')->get();
        $users    = User::where('is_active', true)->orderBy('name')->get();

        return view('admin.key-accounts.index', compact('accounts', 'users'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'account_code' => ['required', 'string', 'max:50'],
            'name'         => ['required', 'string', 'max:200'],
            'type'         => ['required', 'in:key,growth'],
            'user_id'      => ['nullable', 'exists:users,id'],
        ]);

        $existing = KeyAccount::withTrashed()->where('account_code', $data['account_code'])->first();

        if ($existing && ! $existing->trashed()) {
            if ($existing->user_id === null) {
                // Unassigned placeholder created by gifts import — promote it
                $existing->update($data);
                ActivityLog::record('key_accounts.created', "Created account {$data['account_code']}");
                return back()->with('success', "Account {$data['account_code']} created.");
            }
            return back()->withErrors(['account_code' => 'Account code already exists.'])->withInput();
        }

        if ($existing && $existing->trashed()) {
            $existing->restore();
            $existing->update($data);
            ActivityLog::record('key_accounts.created', "Restored account {$data['account_code']}");
            return back()->with('success', "Account {$data['account_code']} restored with existing history.");
        }

        KeyAccount::create($data);
        ActivityLog::record('key_accounts.created', "Created account {$data['account_code']}");

        return back()->with('success', "Account {$data['account_code']} created.");
    }

    public function update(Request $request, KeyAccount $keyAccount): RedirectResponse
    {
        $data = $request->validate([
            'account_code' => ['required', 'string', 'max:50', Rule::unique('key_accounts', 'account_code')->ignore($keyAccount->id)->whereNull('deleted_at')],
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
