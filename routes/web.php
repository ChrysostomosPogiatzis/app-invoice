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
Route::get('/legal/{page}', function ($page) {
    $pages = [
        'terms' => [
            'title' => 'Terms & Conditions',
            'content' => 'By accessing and using Gravity ERP platforms, you agree to these Terms and Conditions. Our services map and manage your CRM and financial data under strict compliance. Subscription fees are billed on an automated cycle and require valid payment methods. Any misuse of the multi-tier nodes will result in immediate termination of the service.'
        ],
        'privacy' => [
            'title' => 'Privacy Policy', 
            'content' => 'We securely collect and process your business, personnel, and banking data to provide the Gravity ERP service. Information is strictly encrypted and hosted within compliant European data centers. We never sell your data to third parties. Under GDPR, you have the right to request deletion of your information.'
        ],
        'refund' => [
            'title' => 'Refund Policy', 
            'content' => 'If you are unsatisfied with our SaaS nodes, you may request a refund within 14 days of your initial purchase. Refunds are processed to the original payment method. Subsequent subscription renewals are strictly non-refundable once the billing period has commenced.'
        ],
        'return' => [
            'title' => 'Return Policy', 
            'content' => 'As Gravity ERP provides digital software as a service (SaaS), physical returns do not apply. If you purchase hardware terminals through us, they may be returned within 30 days in original packaging and condition subject to a 10% restocking fee.'
        ],
        'cancellation' => [
            'title' => 'Cancellation Policy', 
            'content' => 'You may cancel your monthly or annual subscription at any time through the Billing settings of your Admin Console. Upon cancellation, your workspace node will remain active until the end of your current paid billing cycle, after which it will be suspended and downgraded.'
        ],
        'delivery' => [
            'title' => 'Delivery Terms', 
            'content' => 'Platform access is delivered instantly upon successful payment. Hardware orders (e.g., POS tools) are dispatched within 2 working days via ACS Courier across Cyprus. Shipping costs are calculated at checkout. Ownership of physical hardware transfers upon signature of delivery.'
        ],
    ];
    if (!isset($pages[$page])) abort(404);
    
    return Inertia::render('Legal', $pages[$page]);
})->name('legal.show');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');
Route::post('/workspaces/switch', [DashboardController::class, 'switchWorkspace'])->middleware(['auth'])->name('workspaces.switch');

Route::middleware('auth')->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])
        ->middleware('super_admin')
        ->name('admin.index');
    Route::post('/admin/workspaces', [AdminController::class, 'storeWorkspace'])
        ->middleware('super_admin')
        ->name('admin.workspaces.store');
    Route::patch('/admin/workspaces/{id}', [AdminController::class, 'updateWorkspace'])
        ->middleware('super_admin')
        ->name('admin.workspaces.update');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])
        ->middleware('super_admin')
        ->name('admin.users.store');
    Route::patch('/admin/users/{id}', [AdminController::class, 'updateUser'])
        ->middleware('super_admin')
        ->name('admin.users.update');
    Route::post('/admin/users/{id}/toggle-admin', [AdminController::class, 'toggleSuperAdmin'])
        ->middleware('super_admin')
        ->name('admin.users.toggle-admin');
    Route::post('/admin/workspaces/{id}/toggle-status', [AdminController::class, 'toggleWorkspaceStatus'])
        ->middleware('super_admin')
        ->name('admin.workspaces.toggle-status');
    Route::post('/admin/workspaces/{id}/record-payment', [AdminController::class, 'recordPayment'])
        ->middleware('super_admin')
        ->name('admin.workspaces.record-payment');

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
    Route::resource('staff-members', \App\Http\Controllers\StaffMemberController::class);
    Route::post('/staff-members/{id}/documents', [\App\Http\Controllers\StaffMemberController::class, 'uploadDocument'])->name('staff-members.documents.store');
    Route::delete('/staff-members/{staffId}/documents/{docId}', [\App\Http\Controllers\StaffMemberController::class, 'destroyDocument'])->name('staff-members.documents.destroy');
    Route::post('/staff-members/{id}/leave', [\App\Http\Controllers\StaffMemberController::class, 'storeLeave'])->name('staff.leave.store');
    Route::patch('/staff-members/{staffId}/leave/{leaveId}/approve', [\App\Http\Controllers\StaffMemberController::class, 'approveLeave'])->name('staff.leave.approve');
    Route::patch('/staff-members/{staffId}/leave/{leaveId}/reject', [\App\Http\Controllers\StaffMemberController::class, 'rejectLeave'])->name('staff.leave.reject');
    Route::post('/banking/{id}/sync',   [BankingController::class, 'sync'])->name('banking.sync');
    Route::post('/banking/{id}/link',   [BankingController::class, 'link'])->name('banking.link');
    Route::post('/banking/{id}/unlink', [BankingController::class, 'unlink'])->name('banking.unlink');
    Route::get('/banking/connect/{id}', [BankingController::class, 'connect'])->name('banking.connect');
    Route::post('/banking/{id}/refresh', [BankingController::class, 'refreshBalances'])->name('banking.refresh-balances');
    Route::get('/banking/callback/{provider}', [BankingController::class, 'callback'])->name('banking.callback');

    // Payments
    Route::get('/payments', [\App\Http\Controllers\PaymentController::class, 'index'])->name('payments.index');
    Route::delete('/payments/{id}', [\App\Http\Controllers\PaymentController::class, 'destroy'])->name('payments.destroy');

    // Invoice email
    Route::post('/invoices/{invoice}/email', [\App\Http\Controllers\InvoiceController::class, 'sendEmail'])->name('invoices.email');
    Route::post('/invoices/{invoice}/void', [\App\Http\Controllers\InvoiceController::class, 'void'])->name('invoices.void');
    Route::post('/invoices/{invoice}/credit-note', [\App\Http\Controllers\InvoiceController::class, 'createCreditNote'])->name('invoices.credit-note');

    // Stock movement log
    Route::get('/products/{product}/movements', [\App\Http\Controllers\ProductController::class, 'movements'])->name('products.movements');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reports/monthly/invoices', [ReportController::class, 'monthlyInvoicesPdf'])->name('reports.monthly.invoices');
    Route::get('/reports/monthly/expenses', [ReportController::class, 'monthlyExpensesPdf'])->name('reports.monthly.expenses');
    Route::get('/reports/monthly/payroll', [ReportController::class, 'monthlyPayrollPdf'])->name('reports.monthly.payroll');
    Route::get('/reports/accountant/export', [ReportController::class, 'accountantExport'])->name('reports.accountant.export');

    Route::post('/product-categories', [App\Http\Controllers\ProductCategoryController::class, 'store'])->name('product-categories.store');
    Route::post('/payments', [App\Http\Controllers\PaymentController::class, 'store'])->name('payments.store');
    Route::get('/reminders', [\App\Http\Controllers\ReminderController::class, 'index'])->name('reminders.index');
    Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    Route::get('/quotes/{quote}/download', [QuoteController::class, 'download'])->name('quotes.download');
    Route::get('/payslips/{expenseId}/download', [\App\Http\Controllers\PayslipController::class, 'download'])->name('payslips.download');
    Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convertToInvoice'])->name('quotes.convert');
    Route::patch('/quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.update-status');
    Route::post('/communications', [\App\Http\Controllers\CommunicationController::class, 'store'])->name('communications.store');
    Route::post('/reminders', [\App\Http\Controllers\ReminderController::class, 'store'])->name('reminders.store');
    Route::delete('/reminders/{id}', [\App\Http\Controllers\ReminderController::class, 'destroy'])->name('reminders.destroy');
});
Route::get('/logout', function () {
    auth()->logout();
    session()->invalidate();
    session()->regenerateToken();
    return redirect()->route('login');
})->name('logout.get');

Route::post('/billing/webhook', [\App\Http\Controllers\BillingController::class, 'handleWebhook'])->name('billing.webhook');

Route::middleware(['auth'])->group(function () {
    Route::match(['get', 'post'], '/billing/checkout', [\App\Http\Controllers\BillingController::class, 'createCheckoutSession'])->name('billing.checkout');
    Route::match(['get', 'post'], '/billing/success', function() { return redirect()->route('dashboard')->with('success', 'Payment successful! Your node has been reactivated.'); })->name('billing.success');
    Route::match(['get', 'post'], '/billing/cancel', function() { return redirect()->route('settings.edit')->with('error', 'Payment was canceled.'); })->name('billing.cancel');
});

require __DIR__ . '/auth.php';
