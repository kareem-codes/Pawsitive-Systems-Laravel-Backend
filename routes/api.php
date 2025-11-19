<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PetController;
use App\Http\Controllers\Api\V1\OwnerController;
use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\MedicalRecordController;
use App\Http\Controllers\Api\V1\VaccinationController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\FileUploadController;
use App\Http\Controllers\Api\V1\InvoicePdfController;
use App\Http\Controllers\Api\V1\VaccinationCardPdfController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\WeightRecordController;
use App\Http\Controllers\Api\V1\CommunicationLogController;
use App\Http\Controllers\Api\V1\SettingController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\PermissionController;
use App\Http\Controllers\Api\V1\ShopOrderController;
use Illuminate\Support\Facades\Artisan;

// CORS test endpoint - works without auth
Route::get('/test-cors', function (Request $request) {
    return response()->json([
        'success' => true,
        'message' => 'CORS is working!',
        'origin' => $request->headers->get('Origin'),
        'method' => $request->method(),
        'timestamp' => now()->toDateTimeString(),
    ]);
});

Route::get(
    '/clear-cache',
    function (Request $request) {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        return response()->json(['message' => 'Cache cleared successfully']);
    }
);
        Route::get('/tst', function (Request $request) {
    return response()->json([
        'message' => 'API is working',
        'origin' => $request->headers->get('Origin'),
        'headers' => $request->headers->all()
    ]);
});

Route::get('/cors-test', function (Request $request) {
    return response()->json([
        'message' => 'CORS test successful',
        'origin' => $request->headers->get('Origin'),
        'method' => $request->method(),
        'env' => [
            'session_domain' => config('session.domain'),
            'sanctum_stateful' => config('sanctum.stateful'),
            'session_same_site' => config('session.same_site'),
            'session_secure' => config('session.secure'),
        ]
    ]);
});

// API Version 1
Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        // Auth
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/user', [AuthController::class, 'user']);
        
        // Global Search
        Route::get('/search', [SearchController::class, 'index']);
        
        // Pets
        Route::apiResource('pets', PetController::class);
        Route::get('pets/{pet}/weight', [WeightRecordController::class, 'index']);
        Route::post('pets/{pet}/weight', [WeightRecordController::class, 'store']);
        Route::put('pets/{pet}/weight/{weightRecord}', [WeightRecordController::class, 'update']);
        Route::delete('pets/{pet}/weight/{weightRecord}', [WeightRecordController::class, 'destroy']);
        Route::get('pets/{pet}/weight/analytics', [WeightRecordController::class, 'analytics']);
        
        // Owners (Pet Owners)
        Route::get('owners/{owner}/statistics', [OwnerController::class, 'statistics']);
        Route::put('owners/{owner}/emergency-contact', [OwnerController::class, 'updateEmergencyContact']);
        Route::apiResource('owners', OwnerController::class);
        
        // Appointments
        Route::get('appointments/slots/available', [AppointmentController::class, 'availableSlots']);
        Route::post('appointments/slots/check', [AppointmentController::class, 'checkSlotAvailability']);
        Route::post('appointments/{appointment}/confirm', [AppointmentController::class, 'confirm']);
        Route::post('appointments/{appointment}/complete', [AppointmentController::class, 'complete']);
        Route::post('appointments/{appointment}/cancel', [AppointmentController::class, 'cancel']);
        Route::post('appointments/{appointment}/no-show', [AppointmentController::class, 'noShow']);
        Route::apiResource('appointments', AppointmentController::class);
        
        // Communication Logs
        Route::get('communication-logs/statistics', [CommunicationLogController::class, 'statistics']);
        Route::apiResource('communication-logs', CommunicationLogController::class);
        
        // Medical Records
        Route::apiResource('medical-records', MedicalRecordController::class);
        
        // Vaccinations
        Route::apiResource('vaccinations', VaccinationController::class);
        
        // Products
        Route::apiResource('products', ProductController::class);
        
        // Invoices
        Route::apiResource('invoices', InvoiceController::class);
        
        // Payments
        Route::apiResource('payments', PaymentController::class);
        
        // Shop Orders (for customers to place orders)
        Route::get('/shop/orders', [ShopOrderController::class, 'index']);
        Route::post('/shop/orders', [ShopOrderController::class, 'store']);
        Route::get('/shop/orders/{invoice}', [ShopOrderController::class, 'show']);
        
        // File Uploads
        Route::post('/pets/{pet}/photo', [FileUploadController::class, 'uploadPetPhoto']);
        Route::delete('/pets/{pet}/photo', [FileUploadController::class, 'deletePetPhoto']);
        Route::post('/documents', [FileUploadController::class, 'uploadDocument']);
        Route::get('/documents', [FileUploadController::class, 'getDocuments']);
        Route::get('/documents/{document}/download', [FileUploadController::class, 'downloadDocument']);
        Route::delete('/documents/{document}', [FileUploadController::class, 'deleteDocument']);
        
        // PDF Generation
        Route::get('/invoices/{invoice}/pdf', [InvoicePdfController::class, 'download']);
        Route::get('/invoices/{invoice}/pdf/preview', [InvoicePdfController::class, 'preview']);
        Route::get('/pets/{pet}/vaccination-card', [VaccinationCardPdfController::class, 'download']);
        Route::get('/pets/{pet}/vaccination-card/preview', [VaccinationCardPdfController::class, 'preview']);
        
        // Stock Management
        Route::get('/stock/low-stock', [StockController::class, 'lowStock']);
        Route::get('/stock/movements', [StockController::class, 'allMovements']);
        Route::get('/products/{product}/stock/history', [StockController::class, 'history']);
        Route::post('/products/{product}/stock/add', [StockController::class, 'addStock']);
        Route::post('/products/{product}/stock/remove', [StockController::class, 'removeStock']);
        Route::post('/products/{product}/stock/adjust', [StockController::class, 'adjustStock']);
        Route::post('/products/{product}/stock/damaged', [StockController::class, 'markDamaged']);
        Route::post('/products/{product}/stock/expired', [StockController::class, 'markExpired']);
        
        // Settings
        Route::get('/settings/groups', [SettingController::class, 'groups']);
        Route::post('/settings/batch', [SettingController::class, 'updateBatch']);
        Route::get('/settings/{key}', [SettingController::class, 'show']);
        Route::post('/settings', [SettingController::class, 'store']);
        Route::delete('/settings/{key}', [SettingController::class, 'destroy']);
        Route::get('/settings', [SettingController::class, 'index']);
        
        // Audit Logs
        Route::get('/audit-logs/for-model', [AuditLogController::class, 'forModel']);
        Route::apiResource('audit-logs', AuditLogController::class)->only(['index', 'show']);
        
        // Reports
        Route::get('/reports/revenue', [ReportController::class, 'revenue']);
        Route::get('/reports/appointments', [ReportController::class, 'appointments']);
        Route::get('/reports/services', [ReportController::class, 'services']);
        Route::get('/reports/product-sales', [ReportController::class, 'productSales']);
        Route::get('/reports/client-retention', [ReportController::class, 'clientRetention']);
        Route::get('/reports/pets', [ReportController::class, 'pets']);
        
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/dashboard/appointments/today', [DashboardController::class, 'todaysAppointments']);
        Route::get('/dashboard/vaccinations/upcoming', [DashboardController::class, 'upcomingVaccinations']);
        Route::get('/dashboard/vaccinations/overdue', [DashboardController::class, 'overdueVaccinations']);
        Route::get('/dashboard/revenue', [DashboardController::class, 'revenueSummary']);
        
        // User Management
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        
        // User management routes with permissions
        Route::middleware('permission:view users')->group(function () {
            Route::get('/users', [UserController::class, 'index']);
            Route::get('/users/{user}', [UserController::class, 'show']);
        });
        
        Route::middleware('permission:create users')->group(function () {
            Route::post('/users', [UserController::class, 'store']);
        });
        
        Route::middleware('permission:edit users')->group(function () {
            Route::put('/users/{user}', [UserController::class, 'update']);
        });
        
        Route::middleware('permission:delete users')->group(function () {
            Route::delete('/users/{user}', [UserController::class, 'destroy']);
        });
        
        Route::middleware('permission:assign roles')->group(function () {
            Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
            Route::post('/users/{user}/revoke-role', [UserController::class, 'revokeRole']);
        });
        
        // Roles & Permissions - requires role management permissions
        Route::middleware('permission:view roles')->group(function () {
            Route::get('/roles', [RoleController::class, 'index']);
            Route::get('/roles/{role}', [RoleController::class, 'show']);
        });
        
        Route::middleware('permission:create roles')->group(function () {
            Route::post('/roles', [RoleController::class, 'store']);
        });
        
        Route::middleware('permission:edit roles')->group(function () {
            Route::put('/roles/{role}', [RoleController::class, 'update']);
        });
        
        Route::middleware('permission:delete roles')->group(function () {
            Route::delete('/roles/{role}', [RoleController::class, 'destroy']);
        });
        
        Route::middleware('permission:view roles')->group(function () {
            Route::get('/permissions', [PermissionController::class, 'index']);
        });
        
        Route::middleware('permission:create roles')->group(function () {
            Route::post('/permissions', [PermissionController::class, 'store']);
        });
        
        Route::middleware('permission:delete roles')->group(function () {
            Route::delete('/permissions/{permission}', [PermissionController::class, 'destroy']);
        });
    });
});
