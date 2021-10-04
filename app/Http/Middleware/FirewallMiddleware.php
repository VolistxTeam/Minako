<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Wikimedia\IPSet;

class FirewallMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $clientIP = $request->getClientIp();

        $ipSet = new IPSet(config('firewall.ipBlacklist', []));

        if ($ipSet->match($clientIP)) {
            return response('', 403);
        }

        $response = $next($request);
        $response->header('X-Powered-By', 'WebShield/2.86');

        return $response;
    }
}