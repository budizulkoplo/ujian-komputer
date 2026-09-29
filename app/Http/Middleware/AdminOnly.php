<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        abort_if($request->user()?->isTeacher(), 403);

        return $next($request);
    }
}
