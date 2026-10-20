<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ImpersonateController extends Controller
{
    public function start(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isMaster(), 403);
        abort_if($user->isMaster(), 403);

        $adminId = auth()->id();
        $adminName = auth()->user()->name;
        session(['impersonating_id' => $adminId]);
        auth()->login($user);
        ActivityLog::record('auth.impersonate_start', "Started impersonating {$user->name}", $adminId);

        return redirect()->route('dashboard');
    }

    public function stop(): RedirectResponse
    {
        abort_unless(session('impersonating_id'), 403);

        $impersonatedName = auth()->user()->name;
        $originalId = session()->pull('impersonating_id');
        auth()->loginUsingId($originalId);
        session(['otp_verified' => true]);
        ActivityLog::record('auth.impersonate_stop', "Stopped impersonating {$impersonatedName}", $originalId);

        return redirect()->route('admin.users.index');
    }
}
