<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class QuotesBetaAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (strtolower(auth()->user()?->email ?? '') !== 'tom.beresford@lockiegroup.com') {
            abort(403);
        }
        return $next($request);
    }
}
