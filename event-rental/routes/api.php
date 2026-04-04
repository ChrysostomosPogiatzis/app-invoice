<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\ExpenseController;

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

Route::post('/login', [App\Http\Controllers\Api\AuthController::class, 'login']);

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

    // Contacts
    Route::get('contacts/lookup', [ContactController::class, 'lookup']);
    Route::post('contacts/lookup-or-create', [ContactController::class, 'lookupOrCreate']);
    Route::get('contacts/{id}/history', [ContactController::class, 'history']);
    Route::post('contacts/{id}/call-logs', [ContactController::class, 'logCall']);
    Route::post('contacts/{id}/reminders', [ContactController::class, 'addReminder']);
    Route::apiResource('contacts', ContactController::class);

    // Inventory
    Route::apiResource('products', ProductController::class);

    // Financials
    Route::apiResource('invoices', InvoiceController::class);
    Route::apiResource('expenses', ExpenseController::class);
    
    // Banking / External Sync
    Route::post('/banking/sync', [App\Http\Controllers\BankingController::class, 'syncNow']);
});
