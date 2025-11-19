<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\Cors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // CORS must be the very first middleware - handle before anything else
        $middleware->prepend([
            Cors::class,
        ]);
        
        // Disable session and cookie middleware for API routes (using token auth only)
        $middleware->statefulApi();
        
        $middleware->api(
            prepend: [
                SetLocale::class,
            ],
            remove: [
                \Illuminate\Cookie\Middleware\EncryptCookies::class,
                \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
                \Illuminate\Session\Middleware\StartSession::class,
            ]
        );
        
        // Exclude API routes from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'api/*',
        ]);
        
        // Register Spatie Permission middleware aliases
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Ensure CORS headers are added even on errors
        $exceptions->respond(function ($response, $exception, $request) {
            // Add CORS headers to error responses
            $origin = $request->headers->get('Origin', '*');
            $allowedOrigins = [
                'https://pawsitive-dashboard.kareem-codes.com',
                'https://pawsitive-owner.kareem-codes.com',
                'http://localhost:3000',
                'http://localhost:3001',
                'http://127.0.0.1:3000',
                'http://127.0.0.1:3001',
            ];
            $allowOrigin = in_array($origin, $allowedOrigins) ? $origin : $allowedOrigins[0];
            
            $response->headers->set('Access-Control-Allow-Origin', $allowOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS, HEAD');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Authorization, X-Requested-With, X-CSRF-TOKEN, X-XSRF-TOKEN, Accept-Language, X-Locale, Origin, Cookie');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            
            return $response;
        });
    })->create();
