<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $permissions = User::PERMISSIONS;
        $modules     = User::MODULES;
        return view('admin.users.create', compact('permissions', 'modules'));
    }

    public function store(Request $request)
    {
        $isMaster   = auth()->user()->isMaster();
        $tabletOnly = $request->boolean('tablet_only');

        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => $tabletOnly ? 'nullable|email|unique:users,email' : 'required|email|unique:users,email',
            'role'         => ['required', 'in:staff' . ($isMaster ? ',master' : '')],
            'password'     => $tabletOnly ? 'nullable|string' : ['required', Password::min(8)->mixedCase()->numbers()],
            'operator_pin' => $tabletOnly ? 'required|digits_between:4,8' : 'nullable|digits_between:4,8',
        ]);

        $permissions = null;
        $modules     = null;
        if ($data['role'] === 'staff') {
            $permissions = array_keys(array_filter(
                User::PERMISSIONS,
                fn($label, $key) => $request->boolean('perm_' . $key),
                ARRAY_FILTER_USE_BOTH
            ));
            $checked = array_keys(array_filter(
                User::MODULES,
                fn($label, $key) => $request->boolean('mod_' . $key),
                ARRAY_FILTER_USE_BOTH
            ));
            $modules = count($checked) === count(User::MODULES) ? null : $checked;
        }

        $createData = [
            'name'         => $data['name'],
            'email'        => isset($data['email']) ? strtolower($data['email']) : null,
            'role'         => $data['role'],
            'permissions'  => $permissions,
            'modules'      => $modules,
            'password'     => Hash::make($data['password'] ?? \Illuminate\Support\Str::random(32)),
            'is_active'    => true,
            'operator_pin' => $data['operator_pin'] ?? null,
        ];

        User::create($createData);

        $identifier = $data['email'] ?? "PIN {$data['operator_pin']}";
        \App\Models\ActivityLog::record('users.create', "Created user: {$data['name']} ({$identifier})");

        return redirect()->route('admin.users.index')->with('success', "{$data['name']} has been added.");
    }

    public function edit(User $user)
    {
        $permissions = User::PERMISSIONS;
        $modules     = User::MODULES;
        return view('admin.users.edit', compact('user', 'permissions', 'modules'));
    }

    public function update(Request $request, User $user)
    {
        $isMaster   = auth()->user()->isMaster();
        $tabletOnly = $request->boolean('tablet_only');

        $data = $request->validate([
            'name'         => 'required|string|max:100',
            'email'        => $tabletOnly ? 'nullable|email|unique:users,email,' . $user->id : 'required|email|unique:users,email,' . $user->id,
            'role'         => ['required', 'in:staff' . ($isMaster ? ',master' : '')],
            'password'     => $tabletOnly ? ['nullable', 'string'] : ['nullable', Password::min(8)->mixedCase()->numbers()],
            'operator_pin' => $tabletOnly ? 'required|digits_between:4,8' : 'nullable|digits_between:4,8',
        ]);

        $user->name      = $data['name'];
        $user->email     = isset($data['email']) ? strtolower($data['email']) : null;
        $user->role      = $data['role'];
        $user->is_active = $request->boolean('is_active');

        if ($data['role'] === 'staff') {
            $user->permissions = array_keys(array_filter(
                User::PERMISSIONS,
                fn($label, $key) => $request->boolean('perm_' . $key),
                ARRAY_FILTER_USE_BOTH
            ));
            $checked = array_keys(array_filter(
                User::MODULES,
                fn($label, $key) => $request->boolean('mod_' . $key),
                ARRAY_FILTER_USE_BOTH
            ));
            $user->modules = count($checked) === count(User::MODULES) ? null : $checked;
        } else {
            // master — no restrictions needed
            $user->permissions = null;
            $user->modules     = null;
        }

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if (!empty($data['operator_pin'])) {
            $user->operator_pin = $data['operator_pin'];
        } elseif ($request->boolean('clear_operator_pin')) {
            $user->operator_pin = null;
        }

        $user->save();

        \App\Models\ActivityLog::record('users.update', "Updated user: {$user->name}");

        return redirect()->route('admin.users.index')->with('success', "{$user->name} has been updated.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        \App\Models\ActivityLog::record('users.delete', "Deleted user: {$user->name} ({$user->email})");

        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User removed.');
    }
}
