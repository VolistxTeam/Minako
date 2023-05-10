<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdjustRoutesMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $path = $request->getPathInfo();

        // if the path starts with /project
        if (str_starts_with($path, '/minako')) {
            // remove /project from the path
            $newPath = substr($path, 8) ?: '/';
            $request->server->set('REQUEST_URI', $newPath);
        }

        return $next($request);
    }
}
