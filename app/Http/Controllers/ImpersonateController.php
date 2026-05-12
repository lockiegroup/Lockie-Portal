<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ImpersonateController extends Controller
{
    public function start(User $user): RedirectResponse
    {
        abort_unless(auth()->user()->isMaster(), 403);
        abort_if($user->isMaster(), 403);

        session(['impersonating_id' => auth()->id()]);
        auth()->login($user);

        return redirect()->route('dashboard');
    }

    public function stop(): RedirectResponse
    {
        $originalId = session()->pull('impersonating_id');

        if ($originalId) {
            auth()->loginUsingId($originalId);
        }

        return redirect()->route('admin.users.index');
    }
}
