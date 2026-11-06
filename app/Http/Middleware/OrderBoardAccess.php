<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class OrderBoardAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->user()?->hasModule('order_board')) {
            abort(403);
        }
        return $next($request);
    }
}
