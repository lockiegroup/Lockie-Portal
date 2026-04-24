<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\TrustedDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function show()
    {
        if (!session('otp_user_id')) return redirect()->route('login');
        return view('auth.otp');
    }

    public function verify(Request $request)
    {
        $request->validate(['code' => 'required|digits:6']);

        $userId = session('otp_user_id');
        if (!$userId) return redirect()->route('login');

        $otp = \App\Models\OtpCode::where('user_id', $userId)
            ->where('code', $request->code)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$otp) {
            return back()->withErrors(['code' => 'Invalid or expired code. Please try again.']);
        }

        $otp->update(['used' => true]);
        session()->forget('otp_user_id');
        session(['otp_verified' => true]);

        $user = \Illuminate\Support\Facades\Auth::loginUsingId($userId);
        $user->update(['last_login_at' => now()]);

        \App\Models\ActivityLog::record('auth.login', 'Logged in', $user->id);

        if ($request->boolean('trust_device')) {
            $plainToken = Str::random(64);
            TrustedDevice::create([
                'user_id'    => $user->id,
                'token'      => hash('sha256', $plainToken),
                'expires_at' => now()->addDays(30),
            ]);
            TrustedDevice::where('user_id', $user->id)->where('expires_at', '<', now())->delete();

            return redirect()->route('dashboard')
                ->withCookie(cookie('trusted_device', $plainToken, 60 * 24 * 30, '/', null, null, true));
        }

        return redirect()->route('dashboard');
    }
}
