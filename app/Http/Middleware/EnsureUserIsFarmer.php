<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsFarmer
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || ! $request->user()->isFarmer()) {
            abort(403, 'Unauthorized. Farmer access required.');
        }

        return $next($request);
    }
}
