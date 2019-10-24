<?php

namespace Qklin\AutoRouter\Middleware;

use App\Http\Services\ApiThrottleService;
use Closure;
use Illuminate\Http\Request;


class ApiThrottleMiddleware
{
    /**
     * @param Request $request
     * @param Closure $next
     * @param int     $ttl
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $ttl = 3)
    {
        app("autorouter.middleware.apithrottle")->check($request, $ttl);

        return $next($request);
    }
}