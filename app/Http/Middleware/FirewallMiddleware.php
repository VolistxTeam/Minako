<?php

namespace App\Http\Middleware;

use Closure;

class FirewallMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $clientIP = $request->getClientIp();

        if (in_array($clientIP, config('firewall.ipBlacklist', []))) {
            return response('', 403);
        }

        $geoIPLookup = geoip()->getLocation($clientIP);

        if (in_array($geoIPLookup->iso_code, config('firewall.countryBlacklist', []))) {
            return response('', 403);
        }

        return $next($request);
    }
}
