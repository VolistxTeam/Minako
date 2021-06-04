<?php

namespace App\Http\Middleware;

use App\Models\AccessKeys;
use Closure;

class AdminAuthMiddleware
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
        $accessKey = AccessKeys::where('token', $request->input('access_key', ''))->first();

        if (empty($accessKey)) {
            return response()->json([
                'error' => [
                    'type' => 'xInvalidToken',
                    'info' => 'Invalid token was specified or do not have permission.'
                ]
            ], 403);
        }

        $clientIPRange = $this->checkIPRange($request->getClientIp(), $accessKey->whitelist_range);

        if ($clientIPRange === FALSE) {
            return response()->json([
                'error' => [
                    'type' => 'xInvalidToken',
                    'info' => 'Invalid token was specified or do not have permission.'
                ]
            ], 403);
        }

        return $next($request);
    }

    protected function checkIPRange($ip, $range)
    {
        if (empty($range)) {
            return true;
        }

        if (strpos($range, '/') == false) {
            $range .= '/32';
        }

        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~$wildcard_decimal;
        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}
