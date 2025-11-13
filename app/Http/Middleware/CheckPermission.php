<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!auth()->user()->hasPermissionTo($permission)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have the required permission.'
            ], 403);
        }

        return $next($request);
    }
}
