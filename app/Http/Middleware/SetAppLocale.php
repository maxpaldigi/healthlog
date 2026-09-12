<?php

namespace App\Http\Middleware;

use App\Support\AppLocale;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetAppLocale
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale(AppLocale::resolve($request));

        $response = $next($request);

        $response->headers->setCookie(
            cookie(AppLocale::COOKIE, app()->getLocale(), 60 * 24 * 365),
        );

        return $response;
    }
}
