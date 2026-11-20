<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Applies the business language preference (english | filipino) as the app locale. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($request->user()?->business?->language === 'filipino' ? 'fil' : 'en');

        return $next($request);
    }
}
