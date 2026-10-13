<?php

namespace App\Http\Middleware;

use App\Models\TrustedDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class OtpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        // Fast path — session is live and OTP was already verified this session.
        if (session('otp_verified') && auth()->check()) {
            return $next($request);
        }

        // Slow path — session has expired or the remember cookie has been lost.
        // Look up the trusted device cookie directly in the database so we can
        // re-authenticate without first needing auth()->check() to pass.
        $cookieToken = $request->cookie('trusted_device');
        if ($cookieToken) {
            $device = TrustedDevice::where('token', hash('sha256', $cookieToken))
                ->where('expires_at', '>', now())
                ->first();

            if ($device) {
                // Extend the session cookie to 30 days for this response.
                // StartSession reads config('session.lifetime') when it writes
                // the Set-Cookie header, which happens after this middleware
                // returns, so the change is picked up in time.
                config(['session.lifetime' => 60 * 24 * 30]);

                // Re-establish a full authenticated session + refresh the
                // remember cookie so the next session expiry is also seamless.
                Auth::loginUsingId($device->user_id, true);
                session(['otp_verified' => true]);

                return $next($request);
            }
        }

        // No valid session and no valid trusted device.
        // Do NOT call auth()->logout() here — that queues a Set-Cookie which
        // deletes the remember cookie, making silent recovery impossible on
        // the next request (background tabs, AJAX polls, etc.).
        return redirect()->route('login');
    }
}
