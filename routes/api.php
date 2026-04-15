<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\QuoteController;
use App\Http\Controllers\Api\ReminderController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login'])->middleware('throttle:5,1');

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/logout', [App\Http\Controllers\Api\AuthController::class, 'logout']);
    // Current User / Workspace Context
    Route::get('/user', function (Request $request) {
        return [
            'user' => $request->user(),
            'current_workspace' => $request->user()->currentWorkspaceRecord(),
            'workspaces' => $request->user()->workspaces,
        ];
    });

    // Resource endpoints for mobile app / external
    Route::middleware('throttle:60,1')->name('api.')->group(function() {
        // Contacts
        Route::get('contacts/lookup', [ContactController::class, 'lookup']);
        Route::post('contacts/lookup-or-create', [ContactController::class, 'lookupOrCreate']);
        Route::get('contacts/{id}/history', [ContactController::class, 'history']);
        Route::post('contacts/{id}/call-logs', [ContactController::class, 'logCall']);
        Route::post('contacts/{id}/reminders', [ContactController::class, 'addReminder']);
        Route::apiResource('contacts', ContactController::class);

        // Inventory
        Route::apiResource('products', ProductController::class);
        Route::post('products/{id}/adjust-stock', [\App\Http\Controllers\Api\ProductController::class, 'adjustStock']);

        // Staff
        Route::get('staff', [\App\Http\Controllers\Api\StaffController::class, 'index']);
        Route::get('staff/on-leave-today', [\App\Http\Controllers\Api\StaffController::class, 'onLeaveToday']);
        Route::get('staff/{id}', [\App\Http\Controllers\Api\StaffController::class, 'show']);
        Route::get('staff/{id}/leave', [\App\Http\Controllers\Api\StaffController::class, 'leaveRequests']);
        Route::post('staff/{id}/leave', [\App\Http\Controllers\Api\StaffController::class, 'storeLeave']);

        // Financials
        Route::get('invoices/{id}/download', [InvoiceController::class, 'download'])->middleware('throttle:10,1');
        Route::apiResource('invoices', InvoiceController::class);
        Route::get('quotes/{id}/download', [QuoteController::class, 'download'])->middleware('throttle:10,1');
        Route::apiResource('quotes', QuoteController::class);
        Route::apiResource('expenses', ExpenseController::class);
        Route::apiResource('reminders', ReminderController::class);
    });
    
    // Dashboard / Reporting
    Route::group(['prefix' => 'dashboard', 'as' => 'dashboard.'], function () {
        Route::get('/calendar', [App\Http\Controllers\Api\DashboardController::class, 'calendar'])->name('calendar');
        Route::get('/summary', [App\Http\Controllers\Api\DashboardController::class, 'summary'])->name('summary');
    });

    // Bulk Synchronization
    Route::group(['prefix' => 'sync', 'as' => 'sync.', 'middleware' => 'throttle:10,1'], function () {
        Route::post('/contacts', [App\Http\Controllers\Api\SyncController::class, 'syncContacts'])->name('contacts');
        Route::post('/logs', [App\Http\Controllers\Api\SyncController::class, 'syncCallLogs'])->name('logs');
    });

    // Banking / External Sync
    Route::post('/banking/sync', [App\Http\Controllers\BankingController::class, 'syncNow'])->middleware('throttle:5,1');
});
