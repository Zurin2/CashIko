<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** App routes require an onboarded Business; new users land on /onboarding. */
class EnsureBusinessExists
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()->business) {
            return redirect()->to('/onboarding');
        }

        return $next($request);
    }
}
