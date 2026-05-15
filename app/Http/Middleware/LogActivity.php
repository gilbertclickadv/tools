<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log successful GET requests for non-internal/api paths to keep it clean
        // Or log everything if the user wants "all activity"
        if ($request->isMethod('GET') && ! $request->is('api/*') && ! $request->is('_debugbar/*') && ! $request->user()?->is_admin) {
            \App\Models\ActivityLog::create([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'path' => $request->path(),
                'method' => $request->method(),
                'user_id' => auth()->id(),
            ]);
        }

        return $response;
    }
}
