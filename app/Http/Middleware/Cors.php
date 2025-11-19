<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Cors
{
    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS request IMMEDIATELY
        if ($request->isMethod('OPTIONS')) {
            $response = response('', 204);
            $this->addCorsHeaders($response, $request);
            return $response;
        }

        // Process the actual request
        $response = $next($request);
        
        // Add CORS headers to the response
        $this->addCorsHeaders($response, $request);

        return $response;
    }

    private function addCorsHeaders($response, Request $request): void
    {
        $origin = $request->headers->get('Origin', '*');
        
        $allowedOrigins = [
            'https://pawsitive-dashboard.kareem-codes.com',
            'https://pawsitive-owners.kareem-codes.com',
            'http://localhost:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
        ];
        
        // MUST use specific origin (not *) because InfinityFree requires credentials
        $allowOrigin = in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];
        
        $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Accept-Language, X-Locale, Origin, Cookie');
        $response->headers->set('Access-Control-Allow-Credentials', 'true'); // Required for InfinityFree's __test cookie
        $response->headers->set('Access-Control-Expose-Headers', 'Authorization, Content-Length, X-Total-Count, X-Page-Count, Set-Cookie');
        $response->headers->set('Access-Control-Max-Age', '86400');
    }
}
