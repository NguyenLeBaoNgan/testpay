<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySePayWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-SePay-Token');
        $expectedToken = config('sepay.webhook_token'); 

        if ($token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'error' => [
                    'message' => 'Invalid or missing token',
                ]
            ], 403);
        }
        return $next($request);
    }
}
