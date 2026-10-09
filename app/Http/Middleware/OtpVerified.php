<?php

namespace App\Http\Middleware;

use App\Models\TrustedDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OtpVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check()) {
            auth()->logout();
            return redirect()->route('login');
        }

        if (!session('otp_verified')) {
            // Session expired but the user may have a valid trusted device cookie —
            // re-establish otp_verified without forcing them to log in again.
            $cookieToken = $request->cookie('trusted_device');
            if ($cookieToken) {
                $device = TrustedDevice::where('user_id', auth()->id())
                    ->where('token', hash('sha256', $cookieToken))
                    ->where('expires_at', '>', now())
                    ->first();

                if ($device) {
                    session(['otp_verified' => true]);
                    return $next($request);
                }
            }

            auth()->logout();
            return redirect()->route('login');
        }

        return $next($request);
    }
}
