<?php

namespace App\Http\Controllers;

use App\Models\CallLog;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $workspaceId = auth()->user()->currentWorkspaceRecord()?->id ?? auth()->user()->workspaces()->first()->id;
        $now = now();
        $selectedPeriod = $request->string('period')->toString();
        $period = $this->resolvePeriod($selectedPeriod, $now);
        $currentStart = $period['current_start'];
        $currentEnd = $period['current_end'];
        $comparisonStart = $period['comparison_start'];
        $comparisonEnd = $period['comparison_end'];

        $invoiceQuery = Invoice::query()->where('workspace_id', $workspaceId);
        $expenseQuery = Expense::query()->where('workspace_id', $workspaceId);
        $contactQuery = Contact::query()->where('workspace_id', $workspaceId);
        $productQuery = Product::query()->where('workspace_id', $workspaceId);
        $quoteQuery = Quote::query()->where('workspace_id', $workspaceId);
        $reminderQuery = Reminder::query()->whereHas('contact', fn($query) => $query->where('workspace_id', $workspaceId));
        $callLogQuery = CallLog::query()->whereHas('contact', fn($query) => $query->where('workspace_id', $workspaceId));

        $totalRevenue = (float) (clone $invoiceQuery)->sum('grand_total_gross');
        $totalExpenses = (float) (clone $expenseQuery)->sum('amount');
        $activeContacts = (clone $contactQuery)->count();
        $lowStockItems = (clone $productQuery)->where('current_stock', '<', 10)->count();
        $openReceivables = (float) (clone $invoiceQuery)->whereIn('status', ['unpaid', 'partial'])->sum('balance_due');
        $quotePipeline = (float) (clone $quoteQuery)->whereIn('status', ['draft', 'sent', 'viewed', 'accepted'])->sum('grand_total_gross');
        $periodRevenue = (float) (clone $invoiceQuery)->whereBetween('date', [$currentStart->toDateString(), $currentEnd->toDateString()])->sum('grand_total_gross');
        $periodExpenses = (float) (clone $expenseQuery)->whereBetween('expense_date', [$currentStart->toDateString(), $currentEnd->toDateString()])->sum('amount');
        $comparisonRevenue = (float) (clone $invoiceQuery)->whereBetween('date', [$comparisonStart->toDateString(), $comparisonEnd->toDateString()])->sum('grand_total_gross');
        $comparisonExpenses = (float) (clone $expenseQuery)->whereBetween('expense_date', [$comparisonStart->toDateString(), $comparisonEnd->toDateString()])->sum('amount');
        $paymentsThisPeriod = (float) Payment::query()
            ->whereHas('invoice', fn($query) => $query->where('workspace_id', $workspaceId))
            ->whereBetween('payment_date', [$currentStart->copy()->startOfDay(), $currentEnd->copy()->endOfDay()])
            ->sum('amount');
        $comparisonPayments = (float) Payment::query()
            ->whereHas('invoice', fn($query) => $query->where('workspace_id', $workspaceId))
            ->whereBetween('payment_date', [$comparisonStart->copy()->startOfDay(), $comparisonEnd->copy()->endOfDay()])
            ->sum('amount');
        $overdueInvoices = (clone $invoiceQuery)
            ->whereDate('due_date', '<', $now->toDateString())
            ->whereIn('status', ['unpaid', 'partial'])
            ->count();
        $remindersDueSoon = (clone $reminderQuery)
            ->whereBetween('remind_at', [$now->copy()->startOfDay(), $now->copy()->addDays(7)->endOfDay()])
            ->count();

        $recentInvoices = (clone $invoiceQuery)
            ->with('contact')
            ->orderByDesc('date')
            ->limit(6)
            ->get();

        $recentContacts = (clone $contactQuery)
            ->orderByDesc('created_at')
            ->limit(6)
            ->get();

        $topCustomers = Contact::query()
            ->where('workspace_id', $workspaceId)
            ->withSum('invoices', 'grand_total_gross')
            ->orderByDesc('invoices_sum_grand_total_gross')
            ->limit(5)
            ->get()
            ->map(fn(Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'company_name' => $contact->company_name,
                'revenue' => (float) ($contact->invoices_sum_grand_total_gross ?? 0),
            ]);

        $recentPayments = Payment::query()
            ->whereHas('invoice', fn($query) => $query->where('workspace_id', $workspaceId))
            ->with(['invoice.contact'])
            ->orderByDesc('payment_date')
            ->limit(6)
            ->get()
            ->map(fn(Payment $payment) => [
                'id' => $payment->id,
                'amount' => (float) $payment->amount,
                'payment_date' => $payment->payment_date,
                'reference' => $payment->reference,
                'invoice_number' => $payment->invoice?->invoice_number,
                'contact_name' => $payment->invoice?->contact?->name,
            ]);

        $stockWatch = (clone $productQuery)
            ->where('current_stock', '<', 10)
            ->orderBy('current_stock')
            ->limit(6)
            ->get(['id', 'name', 'sku', 'current_stock', 'product_type']);

        $quotesExpiring = (clone $quoteQuery)
            ->with('contact')
            ->whereIn('status', ['draft', 'sent', 'viewed', 'accepted'])
            ->whereBetween('valid_until', [$now->toDateString(), $now->copy()->addDays(14)->toDateString()])
            ->orderBy('valid_until')
            ->limit(6)
            ->get();

        $events = $this->buildCalendarEvents($workspaceId);

        return Inertia::render('Dashboard', [
            'stats' => [
                'totalRevenue' => $totalRevenue,
                'totalExpenses' => $totalExpenses,
                'activeContacts' => $activeContacts,
                'lowStockItems' => $lowStockItems,
                'openReceivables' => $openReceivables,
                'quotePipeline' => $quotePipeline,
                'paymentsThisPeriod' => $paymentsThisPeriod,
                'overdueInvoices' => $overdueInvoices,
                'remindersDueSoon' => $remindersDueSoon,
                'periodPerformance' => [
                    'revenue' => $periodRevenue,
                    'expenses' => $periodExpenses,
                    'margin' => $periodRevenue - $periodExpenses,
                ],
                'comparisonPerformance' => [
                    'revenue' => $comparisonRevenue,
                    'expenses' => $comparisonExpenses,
                    'margin' => $comparisonRevenue - $comparisonExpenses,
                    'payments' => $comparisonPayments,
                ],
            ],
            'period' => [
                'key' => $period['key'],
                'label' => $period['label'],
                'currentLabel' => $period['current_label'],
                'comparisonLabel' => $period['comparison_label'],
                'options' => [
                    ['key' => '30d', 'label' => 'Last 30 Days'],
                    ['key' => '3m', 'label' => 'Last 3 Months'],
                    ['key' => 'ytd', 'label' => 'This Year'],
                ],
            ],
            'recentInvoices' => $recentInvoices,
            'recentContacts' => $recentContacts,
            'recentPayments' => $recentPayments,
            'topCustomers' => $topCustomers,
            'stockWatch' => $stockWatch,
            'quotesExpiring' => $quotesExpiring,
            'calendarEvents' => $events,
        ]);
    }

    protected function buildCalendarEvents(int $workspaceId)
    {
        $invoices = Invoice::where('workspace_id', $workspaceId)->with('contact')->get()->map(fn($invoice) => [
            'title' => 'INV ' . $invoice->invoice_number . ' · ' . ($invoice->contact->name ?? 'Unknown'),
            'date' => $invoice->date,
            'type' => 'invoice',
            'amount' => $invoice->grand_total_gross,
            'url' => route('invoices.show', $invoice->id),
        ]);

        $quotes = Quote::where('workspace_id', $workspaceId)->with('contact')->get()->map(fn($quote) => [
            'title' => 'QUOTE ' . $quote->quote_number . ' · ' . ($quote->contact->name ?? 'Unknown'),
            'date' => $quote->valid_until,
            'type' => 'quote',
            'amount' => $quote->grand_total_gross,
            'url' => route('quotes.show', $quote->id),
        ]);

        $reminders = Reminder::whereHas('contact', fn($query) => $query->where('workspace_id', $workspaceId))->with('contact')->get()->map(fn($reminder) => [
            'title' => 'REMINDER · ' . $reminder->title,
            'date' => $reminder->remind_at,
            'time' => $reminder->remind_at ? substr($reminder->remind_at, 11, 5) : null,
            'type' => 'reminder',
            'url' => route('contacts.show', $reminder->contact_id),
        ]);

        $communications = CallLog::whereHas('contact', fn($query) => $query->where('workspace_id', $workspaceId))->with('contact')->get()->map(fn($log) => [
            'title' => strtoupper($log->call_type) . ' · ' . ($log->contact->name ?? 'Unknown'),
            'date' => $log->call_date,
            'time' => $log->call_date ? Carbon::parse($log->call_date)->format('H:i') : null,
            'type' => 'comm',
            'url' => route('contacts.show', $log->contact_id),
        ]);

        $expenses = Expense::where('workspace_id', $workspaceId)->get()->map(fn($expense) => [
            'title' => 'EXPENSE · ' . ($expense->vendor_name ?: $expense->category),
            'date' => $expense->expense_date,
            'time' => $expense->reminder_time,
            'type' => 'expense',
            'amount' => $expense->amount,
            'url' => route('expenses.index'),
        ]);

        return collect($invoices)
            ->concat($quotes)
            ->concat($reminders)
            ->concat($communications)
            ->concat($expenses)
            ->values();
    }

    protected function resolvePeriod(string $selectedPeriod, Carbon $now): array
    {
        return match ($selectedPeriod) {
            '3m' => [
                'key' => '3m',
                'label' => 'Last 3 Months',
                'current_start' => $now->copy()->subMonthsNoOverflow(2)->startOfMonth(),
                'current_end' => $now->copy()->endOfDay(),
                'comparison_start' => $now->copy()->subMonthsNoOverflow(5)->startOfMonth(),
                'comparison_end' => $now->copy()->subMonthsNoOverflow(3)->endOfMonth(),
                'current_label' => $now->copy()->subMonthsNoOverflow(2)->startOfMonth()->format('d M Y') . ' - ' . $now->copy()->format('d M Y'),
                'comparison_label' => $now->copy()->subMonthsNoOverflow(5)->startOfMonth()->format('d M Y') . ' - ' . $now->copy()->subMonthsNoOverflow(3)->endOfMonth()->format('d M Y'),
            ],
            'ytd' => [
                'key' => 'ytd',
                'label' => 'This Year',
                'current_start' => $now->copy()->startOfYear(),
                'current_end' => $now->copy()->endOfDay(),
                'comparison_start' => $now->copy()->subYear()->startOfYear(),
                'comparison_end' => $now->copy()->subYear()->startOfYear()->addDays($now->copy()->startOfYear()->diffInDays($now))->endOfDay(),
                'current_label' => $now->copy()->startOfYear()->format('d M Y') . ' - ' . $now->copy()->format('d M Y'),
                'comparison_label' => $now->copy()->subYear()->startOfYear()->format('d M Y') . ' - ' . $now->copy()->subYear()->startOfYear()->addDays($now->copy()->startOfYear()->diffInDays($now))->format('d M Y'),
            ],
            default => [
                'key' => '30d',
                'label' => 'Last 30 Days',
                'current_start' => $now->copy()->subDays(29)->startOfDay(),
                'current_end' => $now->copy()->endOfDay(),
                'comparison_start' => $now->copy()->subDays(59)->startOfDay(),
                'comparison_end' => $now->copy()->subDays(30)->endOfDay(),
                'current_label' => $now->copy()->subDays(29)->format('d M Y') . ' - ' . $now->copy()->format('d M Y'),
                'comparison_label' => $now->copy()->subDays(59)->format('d M Y') . ' - ' . $now->copy()->subDays(30)->format('d M Y'),
            ],
        };
    }
}
