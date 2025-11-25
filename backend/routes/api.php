<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\MonitoringController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Public routes
Route::post('/auth/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Authentication
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/profile', [AuthController::class, 'updateProfile']);
        Route::put('/password', [AuthController::class, 'changePassword']);
    });

    // Customers
    Route::apiResource('customers', CustomerController::class);
    Route::post('/customers/{customer}/suspend', [CustomerController::class, 'suspend']);
    Route::post('/customers/{customer}/activate', [CustomerController::class, 'activate']);

    // Subscriptions
    Route::apiResource('subscriptions', SubscriptionController::class);
    Route::post('/subscriptions/{subscription}/provision', [SubscriptionController::class, 'provision']);
    Route::post('/subscriptions/{subscription}/suspend', [SubscriptionController::class, 'suspend']);
    Route::post('/subscriptions/{subscription}/unsuspend', [SubscriptionController::class, 'unsuspend']);
    Route::post('/subscriptions/{subscription}/reset-session', [SubscriptionController::class, 'resetSession']);
    Route::post('/subscriptions/{subscription}/terminate', [SubscriptionController::class, 'terminate']);

    // Services
    Route::apiResource('services', ServiceController::class);

    // Invoices
    Route::apiResource('invoices', InvoiceController::class);
    Route::post('/invoices/generate', [InvoiceController::class, 'generate']);
    Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel']);
    Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf']);

    // Payments
    Route::apiResource('payments', PaymentController::class)->except(['update']);

    // Devices - Routers
    Route::prefix('devices')->group(function () {
        Route::get('/routers', [DeviceController::class, 'indexRouters']);
        Route::post('/routers', [DeviceController::class, 'storeRouter']);
        Route::get('/routers/{router}', [DeviceController::class, 'showRouter']);
        Route::put('/routers/{router}', [DeviceController::class, 'updateRouter']);
        Route::delete('/routers/{router}', [DeviceController::class, 'destroyRouter']);

        // Devices - OLT
        Route::get('/olt', [DeviceController::class, 'indexOlts']);
        Route::post('/olt', [DeviceController::class, 'storeOlt']);
        Route::get('/olt/{olt}', [DeviceController::class, 'showOlt']);
        Route::put('/olt/{olt}', [DeviceController::class, 'updateOlt']);
        Route::delete('/olt/{olt}', [DeviceController::class, 'destroyOlt']);

        // Devices - ONU
        Route::get('/onu', [DeviceController::class, 'indexOnus']);
        Route::get('/onu/{onu}', [DeviceController::class, 'showOnu']);
    });

    // Monitoring
    Route::prefix('monitoring')->group(function () {
        Route::get('/summary', [MonitoringController::class, 'summary']);
        Route::get('/devices', [MonitoringController::class, 'devices']);
        Route::get('/traffic', [MonitoringController::class, 'traffic']);
        Route::get('/alerts', [MonitoringController::class, 'alerts']);
        Route::get('/chart-data', [MonitoringController::class, 'chartData']);
        Route::get('/recent-activities', [MonitoringController::class, 'recentActivities']);
    });

    // Accounting (TODO: Implement AccountingController)
    // Route::get('/accounting/income-statement', [AccountingController::class, 'incomeStatement']);
    // Route::get('/accounting/balance-sheet', [AccountingController::class, 'balanceSheet']);

    // Dashboard
    Route::get('/dashboard/stats', [App\Http\Controllers\Api\DashboardController::class, 'stats']);
    Route::get('/dashboard/recent-activities', [App\Http\Controllers\Api\DashboardController::class, 'recentActivities']);
    Route::get('/dashboard/chart-data', [App\Http\Controllers\Api\DashboardController::class, 'chartData']);
});
