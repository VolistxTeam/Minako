<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FirewallMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $clientIP = $request->getClientIp();

        if (in_array($clientIP, config('firewall.ipBlacklist', []))) {
            return response('', 403);
        }

        return $next($request);
    }
}
