<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsFarmer
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isFarmer() || $user->farmer?->is_active !== true) {
            abort(403, 'Unauthorized. Farmer access required.');
        }

        return $next($request);
    }
}
