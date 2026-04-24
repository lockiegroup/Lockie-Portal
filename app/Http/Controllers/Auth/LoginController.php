<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\OtpCode;
use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $key = 'login:' . Str::lower($request->email) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return back()->withErrors(['email' => "Too many attempts. Try again in {$seconds} seconds."]);
        }

        $user = User::where('email', Str::lower($request->email))->where('is_active', true)->first();

        if (!$user || !Auth::attempt(['email' => $request->email, 'password' => $request->password], false)) {
            RateLimiter::hit($key, 60);
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput(['email' => $request->email]);
        }

        RateLimiter::clear($key);
        Auth::logout();

        // Skip OTP if this browser has a valid trusted device token for this user
        $cookieToken = $request->cookie('trusted_device');
        if ($cookieToken) {
            $device = TrustedDevice::where('user_id', $user->id)
                ->where('token', hash('sha256', $cookieToken))
                ->where('expires_at', '>', now())
                ->first();

            if ($device) {
                Auth::loginUsingId($user->id);
                $user->update(['last_login_at' => now()]);
                ActivityLog::record('auth.login', 'Logged in (trusted device)', $user->id);
                session(['otp_verified' => true]);
                return redirect()->intended(route('dashboard'));
            }
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        OtpCode::where('user_id', $user->id)->where('used', false)->update(['used' => true]);
        OtpCode::create([
            'user_id'    => $user->id,
            'code'       => $otp,
            'expires_at' => now()->addMinutes(10),
        ]);

        $this->sendOtp($user, $otp);
        session(['otp_user_id' => $user->id]);

        return redirect()->route('otp.show');
    }

    private function sendOtp(User $user, string $otp): void
    {
        if (!config('mail.mailers.smtp.host')) {
            logger()->info("[DEV] OTP for {$user->email}: {$otp}");
            return;
        }

        Mail::raw(
            "Hi {$user->name},\n\nYour Lockie Portal login code is: {$otp}\n\nExpires in 10 minutes.\n\nIf you did not request this, ignore this email.",
            fn($m) => $m->to($user->email, $user->name)->subject("Your Lockie Portal code: {$otp}")
        );
    }
}
