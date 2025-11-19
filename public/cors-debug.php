<?php
/**
 * CORS Debug Script
 * Place this in public/cors-debug.php to test CORS headers directly
 * Access via: https://your-domain.com/cors-debug.php
 */

// Get the origin
$origin = $_SERVER['HTTP_ORIGIN'] ?? 'Unknown';

// List of allowed origins
$allowedOrigins = [
    'https://pawsitive-dashboard.kareem-codes.com',
    'https://pawsitive-owner.kareem-codes.com',
    'http://localhost:3000',
    'http://localhost:3001',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:3001',
];

// Determine which origin to allow
$allowOrigin = in_array($origin, $allowedOrigins) ? $origin : '*';

// Set CORS headers
header("Access-Control-Allow-Origin: $allowOrigin");
header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept, Authorization, X-Requested-With, X-XSRF-TOKEN, X-CSRF-TOKEN, Accept-Language, X-Locale");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Max-Age: 86400");
header("Vary: Origin");
header("Content-Type: application/json");

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Return debug information
$debug = [
    'message' => 'CORS Debug Information',
    'timestamp' => date('Y-m-d H:i:s'),
    'request' => [
        'method' => $_SERVER['REQUEST_METHOD'],
        'origin' => $origin,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? 'Unknown',
    ],
    'cors_headers_sent' => [
        'Access-Control-Allow-Origin' => $allowOrigin,
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Content-Type, Accept, Authorization, X-Requested-With, X-XSRF-TOKEN, X-CSRF-TOKEN, Accept-Language, X-Locale',
        'Access-Control-Allow-Credentials' => 'true',
    ],
    'server_info' => [
        'php_version' => phpversion(),
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    ],
    'all_headers' => getallheaders(),
];

echo json_encode($debug, JSON_PRETTY_PRINT);
