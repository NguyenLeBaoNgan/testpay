<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (auth()->check()) {
            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => $request->method() . ' ' . $request->path(),
                'result' => $response->getStatusCode() == 200 ? 'Success' : 'Failed',
                'ip_address' => $request->ip(),
                'browser' => $request->header('User-Agent'),
            ]);
        }

        return $response;
    }
}
