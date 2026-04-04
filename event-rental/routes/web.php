<?php

use App\Http\Controllers\DashboardController;

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\QuoteController;

use App\Http\Controllers\ReportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PublicInvoiceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\WorkspaceUserController;
use App\Http\Controllers\BankingController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/shared/invoice/{token}', [PublicInvoiceController::class, 'show'])->name('public.invoice.show');
Route::post('/shared/invoice/{token}/sign', [PublicInvoiceController::class, 'sign'])->name('public.invoice.sign');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])
        ->middleware('super_admin')
        ->name('admin.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/settings', [\App\Http\Controllers\SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update'])->name('settings.update');
    Route::post('/settings/tokens', [\App\Http\Controllers\SettingsController::class, 'generateToken'])->name('settings.tokens.generate');
    Route::delete('/settings/tokens/{id}', [\App\Http\Controllers\SettingsController::class, 'revokeToken'])->name('settings.tokens.revoke');
    Route::get('/workspace-users', [WorkspaceUserController::class, 'index'])->middleware('workspace_admin')->name('workspace-users.index');
    Route::post('/workspace-users', [WorkspaceUserController::class, 'store'])->middleware('workspace_admin')->name('workspace-users.store');
    Route::patch('/workspace-users/{user}', [WorkspaceUserController::class, 'update'])->middleware('workspace_admin')->name('workspace-users.update');
    Route::delete('/workspace-users/{user}', [WorkspaceUserController::class, 'destroy'])->middleware('workspace_admin')->name('workspace-users.destroy');


    Route::resource('products', ProductController::class);
    Route::patch('products/{product}/update-partial', [ProductController::class, 'updatePartial'])->name('products.update-partial');
    Route::post('products/{product}/adjust-stock', [ProductController::class, 'adjustStock'])->name('products.adjust-stock');
    Route::resource('contacts', ContactController::class);
    Route::resource('invoices', InvoiceController::class);
    Route::resource('quotes', QuoteController::class);
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class);
    Route::resource('communications', CommunicationController::class);

    Route::resource('banking', BankingController::class)->except(['show']);
    Route::post('/banking/{id}/sync',   [BankingController::class, 'sync'])->name('banking.sync');
    Route::post('/banking/{id}/link',   [BankingController::class, 'link'])->name('banking.link');
    Route::post('/banking/{id}/unlink', [BankingController::class, 'unlink'])->name('banking.unlink');



    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/monthly/invoices', [ReportController::class, 'monthlyInvoicesPdf'])->name('reports.monthly.invoices');
    Route::get('/reports/monthly/expenses', [ReportController::class, 'monthlyExpensesPdf'])->name('reports.monthly.expenses');

    Route::post('/product-categories', [App\Http\Controllers\ProductCategoryController::class, 'store'])->name('product-categories.store');
    Route::post('/payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
    Route::get('/reminders', [\App\Http\Controllers\ReminderController::class, 'index'])->name('reminders.index');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convertToInvoice'])->name('quotes.convert');
    Route::patch('/quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.update-status');
    Route::post('/communications', [\App\Http\Controllers\CommunicationController::class, 'store'])->name('communications.store');
    Route::post('/reminders', [\App\Http\Controllers\ReminderController::class, 'store'])->name('reminders.store');
    Route::delete('/reminders/{id}', [\App\Http\Controllers\ReminderController::class, 'destroy'])->name('reminders.destroy');
});

require __DIR__ . '/auth.php';
