<?php

namespace App\Http\Middleware;

use App\Support\SystemTimezone;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectSystemTimezone
{
    public function handle(Request $request, Closure $next): Response
    {
        $timezone = SystemTimezone::detected($request);

        if ($timezone !== null && $request->hasSession()) {
            $request->session()->put('system_timezone', $timezone);
        }

        return $next($request);
    }
}
