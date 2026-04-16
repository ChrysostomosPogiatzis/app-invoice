<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CallLog;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\Reminder;
use App\Models\StaffLeaveRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/calendar
    // Backward-compatible: existing fields untouched, leave events added.
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * @OA\Get(path="/api/dashboard/calendar", tags={"Dashboard"}, summary="Get calendar events",
     *     description="Returns all business events (invoices, quotes, reminders, calls, expenses, leaves) within a date range.",
     *     security={{"BearerToken":{}}},
     *     @OA\Parameter(name="start", in="query", description="Start date (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Parameter(name="end", in="query", description="End date (YYYY-MM-DD)", required=false, @OA\Schema(type="string", format="date")),
     *     @OA\Response(response=200, description="Calendar events", @OA\JsonContent(ref="#/components/schemas/DashboardCalendarResponse")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function calendar(Request $request)
    {
        $workspaceId = $request->user()->currentWorkspaceRecord()?->id
            ?? $request->user()->workspaces()->first()->id;

        $start = $request->has('start')
            ? Carbon::parse($request->start)->startOfDay()
            : now()->subDays(15)->startOfDay();
        $end = $request->has('end')
            ? Carbon::parse($request->end)->endOfDay()
            : now()->addDays(15)->endOfDay();

        // ── Invoices ────────────────────────────────────────────────────────
        $invoices = Invoice::where('workspace_id', $workspaceId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->with('contact:id,name,company_name,mobile_number,email')
            ->get()
            ->map(fn($invoice) => [
                // ← existing fields (unchanged)
                'id'       => $invoice->id,
                'title'    => 'INVOICE ' . $invoice->invoice_number,
                'subtitle' => $invoice->contact->name ?? 'Unknown',
                'date'     => $invoice->date,
                'type'     => 'invoice',
                'amount'   => (float) $invoice->grand_total_gross,
                'phone'    => $invoice->contact->mobile_number ?? '',
                'status'   => $invoice->status,
                // ← new fields (additive)
                'invoice_number'    => $invoice->invoice_number,
                'balance_due'       => (float) $invoice->balance_due,
                'due_date'          => $invoice->due_date,
                'is_overdue'        => $invoice->due_date && $invoice->status !== 'paid'
                    && Carbon::parse($invoice->due_date)->isPast(),
                'contact' => [
                    'id'           => $invoice->contact?->id,
                    'name'         => $invoice->contact?->name,
                    'company_name' => $invoice->contact?->company_name,
                    'email'        => $invoice->contact?->email,
                    'phone'        => $invoice->contact?->mobile_number,
                ],
            ]);

        // ── Quotes ──────────────────────────────────────────────────────────
        $quotes = Quote::where('workspace_id', $workspaceId)
            ->whereBetween('valid_until', [$start->toDateString(), $end->toDateString()])
            ->with('contact:id,name,company_name,mobile_number,email')
            ->get()
            ->map(fn($quote) => [
                // ← existing fields (unchanged)
                'id'       => $quote->id,
                'title'    => 'QUOTE ' . $quote->quote_number,
                'subtitle' => $quote->contact->name ?? 'Unknown',
                'date'     => $quote->valid_until,
                'type'     => 'quote',
                'amount'   => (float) $quote->grand_total_gross,
                'phone'    => $quote->contact->mobile_number ?? '',
                'status'   => $quote->status,
                // ← new fields (additive)
                'quote_number'  => $quote->quote_number,
                'quote_date'    => $quote->date,
                'valid_until'   => $quote->valid_until,
                'is_expiring_soon' => Carbon::parse($quote->valid_until)->diffInDays(now(), false) >= -7
                    && Carbon::parse($quote->valid_until)->isFuture(),
                'contact' => [
                    'id'           => $quote->contact?->id,
                    'name'         => $quote->contact?->name,
                    'company_name' => $quote->contact?->company_name,
                    'email'        => $quote->contact?->email,
                    'phone'        => $quote->contact?->mobile_number,
                ],
            ]);

        // ── Reminders ───────────────────────────────────────────────────────
        $reminders = Reminder::whereHas('contact', fn($q) => $q->where('workspace_id', $workspaceId))
            ->whereBetween('remind_at', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->with('contact:id,name,company_name,mobile_number,email')
            ->get()
            ->map(fn($reminder) => [
                // ← existing fields (unchanged)
                'id'           => $reminder->id,
                'title'        => 'REMINDER: ' . $reminder->title,
                'subtitle'     => $reminder->contact->name ?? 'No Contact',
                'date'         => Carbon::parse($reminder->remind_at)->toDateString(),
                'time'         => Carbon::parse($reminder->remind_at)->format('H:i'),
                'phone'        => $reminder->contact->mobile_number ?? '',
                'type'         => 'reminder',
                'is_completed' => (bool) $reminder->completed_at,
                // ← new fields (additive)
                'remind_at'    => $reminder->remind_at,
                'is_overdue'   => !$reminder->completed_at
                    && Carbon::parse($reminder->remind_at)->isPast(),
                'contact' => [
                    'id'           => $reminder->contact?->id,
                    'name'         => $reminder->contact?->name,
                    'company_name' => $reminder->contact?->company_name,
                    'email'        => $reminder->contact?->email,
                    'phone'        => $reminder->contact?->mobile_number,
                ],
            ]);

        // ── Call Logs ───────────────────────────────────────────────────────
        $communications = CallLog::whereHas('contact', fn($q) => $q->where('workspace_id', $workspaceId))
            ->whereBetween('call_date', [$start->toDateTimeString(), $end->toDateTimeString()])
            ->with('contact:id,name,company_name,mobile_number,email')
            ->get()
            ->map(fn($log) => [
                // ← existing fields (unchanged)
                'id'       => $log->id,
                'title'    => strtoupper($log->call_type) . ' CALL',
                'subtitle' => $log->contact->name ?? 'Unknown',
                'date'     => Carbon::parse($log->call_date)->toDateString(),
                'time'     => Carbon::parse($log->call_date)->format('H:i'),
                'phone'    => $log->contact->mobile_number ?? '',
                'type'     => 'call',
                'duration' => $log->duration,
                // ← new fields (additive)
                'call_type'             => $log->call_type,
                'call_duration_seconds' => $log->call_duration_seconds,
                'call_notes'            => $log->call_notes,
                'call_date'             => $log->call_date,
                'contact' => [
                    'id'           => $log->contact?->id,
                    'name'         => $log->contact?->name,
                    'company_name' => $log->contact?->company_name,
                    'email'        => $log->contact?->email,
                    'phone'        => $log->contact?->mobile_number,
                ],
            ]);

        // ── Expenses ────────────────────────────────────────────────────────
        $expenses = Expense::where('workspace_id', $workspaceId)
            ->whereBetween('expense_date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->map(fn($expense) => [
                // ← existing fields (unchanged)
                'id'       => $expense->id,
                'title'    => 'EXPENSE: ' . ($expense->vendor_name ?: $expense->category),
                'subtitle' => $expense->category,
                'date'     => $expense->expense_date,
                'type'     => 'expense',
                'amount'   => (float) $expense->amount,
                // ← new fields (additive)
                'vendor_name' => $expense->vendor_name,
                'category'    => $expense->category,
                'is_payroll'  => (bool) $expense->is_payroll,
                'vat_amount'  => (float) ($expense->vat_amount ?? 0),
            ]);

        // ── Staff Leave (new addition) ───────────────────────────────────────
        $leaves = StaffLeaveRequest::whereHas('staff', fn($q) => $q->where('workspace_id', $workspaceId))
            ->where('status', '!=', 'rejected')
            ->with('staff')
            ->get()
            ->flatMap(function ($leave) use ($start, $end) {
                $leaveStart = Carbon::parse($leave->start_date);
                $leaveEnd   = Carbon::parse($leave->end_date);
                $days = [];
                for ($i = 0; $i <= $leaveStart->diffInDays($leaveEnd); $i++) {
                    $day = $leaveStart->copy()->addDays($i);
                    if ($day->between($start, $end)) {
                        $days[] = [
                            'id'       => $leave->id,
                            'title'    => strtoupper($leave->type) . ' LEAVE',
                            'subtitle' => $leave->staff->name ?? 'Unknown',
                            'date'     => $day->toDateString(),
                            'type'     => 'leave',
                            'amount'   => 0.0,
                            'phone'    => '',
                            'status'   => $leave->status,
                            // extra
                            'leave_type'       => $leave->type,
                            'leave_start_date' => $leave->start_date,
                            'leave_end_date'   => $leave->end_date,
                            'days_count'       => $leave->days_count,
                            'staff_name'       => $leave->staff->name ?? null,
                        ];
                    }
                }
                return $days;
            });

        $events = collect($invoices)
            ->concat($quotes)
            ->concat($reminders)
            ->concat($communications)
            ->concat($expenses)
            ->concat($leaves)
            ->sortBy('date')
            ->values();

        $perDay = $events->groupBy('date');

        return response()->json([
            // ← existing top-level keys (unchanged)
            'status'        => 'success',
            'range'         => [
                'start' => $start->toDateString(),
                'end'   => $end->toDateString(),
            ],
            'total_events'  => $events->count(),
            'data'          => $perDay,
            'flat_list'     => $events,
            // ← new top-level keys (additive)
            'type_counts'   => [
                'invoices'   => $invoices->count(),
                'quotes'     => $quotes->count(),
                'reminders'  => $reminders->count(),
                'calls'      => $communications->count(),
                'expenses'   => $expenses->count(),
                'leave'      => $leaves->count(),
            ],
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // GET /api/dashboard/summary
    // Backward-compatible: `date`, `events`, `stats` keys untouched.
    // ─────────────────────────────────────────────────────────────────────────
    /**
     * @OA\Get(path="/api/dashboard/summary", tags={"Dashboard"}, summary="Get KPI summary",
     *     description="Returns current financial KPIs, today's events, overdue items, and staff on leave.",
     *     security={{"BearerToken":{}}},
     *     @OA\Response(response=200, description="Dashboard summary", @OA\JsonContent(ref="#/components/schemas/DashboardSummaryResponse")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function summary(Request $request)
    {
        $workspaceId = $request->user()->currentWorkspaceRecord()?->id
            ?? $request->user()->workspaces()->first()->id;
        $now   = now();
        $today = $now->toDateString();

        // ── Existing stats ───────────────────────────────────────────────────
        $activeContacts   = Contact::where('workspace_id', $workspaceId)->count();
        $totalOutstanding = (float) Invoice::where('workspace_id', $workspaceId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('balance_due');

        // ── Existing today events (unchanged mapping) ────────────────────────
        $invoicesToday = Invoice::where('workspace_id', $workspaceId)
            ->where('date', $today)
            ->with('contact')
            ->get()
            ->map(fn($i) => [
                'id'          => $i->id,
                'type'        => 'event',
                'title'       => 'Invoice #' . $i->invoice_number,
                'description' => $i->contact->name ?? 'Unknown',
                'phone'       => $i->contact->mobile_number ?? '',
                'status'      => $i->status,
                'amount'      => number_format($i->grand_total_gross, 2, '.', ''),
                'time'        => 'All Day',
            ]);

        $quotesToday = Quote::where('workspace_id', $workspaceId)
            ->where('valid_until', $today)
            ->with('contact')
            ->get()
            ->map(fn($q) => [
                'id'          => $q->id,
                'type'        => 'event',
                'title'       => 'Quote #' . $q->quote_number,
                'description' => $q->contact->name ?? 'Unknown',
                'phone'       => $q->contact->mobile_number ?? '',
                'status'      => $q->status,
                'amount'      => number_format($q->grand_total_gross, 2, '.', ''),
                'time'        => 'All Day',
            ]);

        $remindersToday = Reminder::whereHas('contact', fn($q) => $q->where('workspace_id', $workspaceId))
            ->whereDate('remind_at', $today)
            ->with('contact')
            ->get()
            ->map(fn($r) => [
                'id'          => $r->id,
                'type'        => 'event',
                'title'       => 'Reminder: ' . $r->title,
                'description' => $r->contact->name ?? 'No Contact',
                'phone'       => $r->contact->mobile_number ?? '',
                'status'      => $r->completed_at ? 'completed' : 'pending',
                'amount'      => '0.00',
                'time'        => $r->remind_at
                    ? Carbon::parse($r->remind_at)->format('H:i')
                    : 'All Day',
            ]);

        // ← `events` key — existing format, preserved exactly
        $events = collect($invoicesToday)->concat($quotesToday)->concat($remindersToday);

        // ── New supplementary data ────────────────────────────────────────────
        // Today's call logs
        $callsToday = CallLog::whereHas('contact', fn($q) => $q->where('workspace_id', $workspaceId))
            ->whereDate('call_date', $today)
            ->with('contact:id,name,mobile_number,email,company_name')
            ->get()
            ->map(fn($log) => [
                'id'                    => $log->id,
                'type'                  => 'call',
                'call_type'             => $log->call_type,
                'contact_name'          => $log->contact->name ?? 'Unknown',
                'contact_phone'         => $log->contact->mobile_number ?? '',
                'contact_company'       => $log->contact->company_name ?? '',
                'call_duration_seconds' => $log->call_duration_seconds,
                'call_notes'            => $log->call_notes,
                'call_date'             => $log->call_date,
                'time'                  => Carbon::parse($log->call_date)->format('H:i'),
            ]);

        // Overdue invoices
        $overdueInvoices = Invoice::where('workspace_id', $workspaceId)
            ->whereIn('status', ['unpaid', 'partial'])
            ->whereDate('due_date', '<', $today)
            ->with('contact:id,name,mobile_number,company_name')
            ->orderBy('due_date')
            ->get()
            ->map(fn($i) => [
                'id'             => $i->id,
                'invoice_number' => $i->invoice_number,
                'contact_name'   => $i->contact->name ?? 'Unknown',
                'contact_phone'  => $i->contact->mobile_number ?? '',
                'balance_due'    => (float) $i->balance_due,
                'due_date'       => $i->due_date,
                'days_overdue'   => (int) Carbon::parse($i->due_date)->diffInDays($now),
            ]);

        // Today's expenses / payroll
        $expensesToday = Expense::where('workspace_id', $workspaceId)
            ->where('expense_date', $today)
            ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'vendor_name' => $e->vendor_name,
                'category'    => $e->category,
                'amount'      => (float) $e->amount,
                'is_payroll'  => (bool) $e->is_payroll,
            ]);

        // Staff on leave today
        $staffOnLeave = StaffLeaveRequest::whereHas('staff', fn($q) => $q->where('workspace_id', $workspaceId))
            ->where('status', '!=', 'rejected')
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->with('staff:id,name,position')
            ->get()
            ->map(fn($leave) => [
                'staff_name'     => $leave->staff->name ?? 'Unknown',
                'staff_position' => $leave->staff->position ?? '',
                'leave_type'     => $leave->type,
                'start_date'     => $leave->start_date,
                'end_date'       => $leave->end_date,
                'days_remaining' => max(0, (int) Carbon::parse($leave->end_date)->diffInDays($now, false) * -1),
            ]);

        // Quotes expiring in the next 7 days
        $quotesExpiringSoon = Quote::where('workspace_id', $workspaceId)
            ->whereIn('status', ['draft', 'sent', 'viewed', 'accepted'])
            ->whereBetween('valid_until', [$today, $now->copy()->addDays(7)->toDateString()])
            ->with('contact:id,name,mobile_number,company_name')
            ->orderBy('valid_until')
            ->get()
            ->map(fn($q) => [
                'id'              => $q->id,
                'quote_number'    => $q->quote_number,
                'contact_name'    => $q->contact->name ?? 'Unknown',
                'contact_phone'   => $q->contact->mobile_number ?? '',
                'status'          => $q->status,
                'grand_total'     => (float) $q->grand_total_gross,
                'valid_until'     => $q->valid_until,
                'days_until_expiry' => (int) Carbon::parse($q->valid_until)->diffInDays($now, false) * -1,
            ]);

        // Financial KPIs (this month vs last month)
        $monthStart     = $now->copy()->startOfMonth();
        $lastMonthStart = $now->copy()->subMonthNoOverflow()->startOfMonth();
        $lastMonthEnd   = $now->copy()->subMonthNoOverflow()->endOfMonth();

        $revenueThisMonth  = (float) Invoice::where('workspace_id', $workspaceId)
            ->whereBetween('date', [$monthStart->toDateString(), $today])
            ->sum('grand_total_gross');
        $revenueLastMonth  = (float) Invoice::where('workspace_id', $workspaceId)
            ->whereBetween('date', [$lastMonthStart->toDateString(), $lastMonthEnd->toDateString()])
            ->sum('grand_total_gross');
        $expensesThisMonth = (float) Expense::where('workspace_id', $workspaceId)
            ->whereBetween('expense_date', [$monthStart->toDateString(), $today])
            ->sum('amount');
        $paymentsThisMonth = (float) Payment::whereHas('invoice', fn($q) => $q->where('workspace_id', $workspaceId))
            ->whereBetween('payment_date', [$monthStart->toDateString(), $today])
            ->sum('amount');

        return response()->json([
            // ─── EXISTING keys (unchanged format) ───────────────────────────
            'date'   => $now->format('M j, Y'),
            'events' => $events->values(),
            'stats'  => [
                'active_contacts'   => $activeContacts,
                'total_outstanding' => number_format($totalOutstanding, 2, '.', ''),
            ],

            // ─── NEW keys (additive — safe to consume optionally) ────────────
            'calls_today'         => $callsToday->values(),
            'overdue_invoices'    => $overdueInvoices->values(),
            'expenses_today'      => $expensesToday->values(),
            'staff_on_leave'      => $staffOnLeave->values(),
            'quotes_expiring_soon'=> $quotesExpiringSoon->values(),

            'kpis' => [
                'revenue_this_month'   => round($revenueThisMonth, 2),
                'revenue_last_month'   => round($revenueLastMonth, 2),
                'expenses_this_month'  => round($expensesThisMonth, 2),
                'payments_this_month'  => round($paymentsThisMonth, 2),
                'margin_this_month'    => round($revenueThisMonth - $expensesThisMonth, 2),
                'total_outstanding'    => round($totalOutstanding, 2),
                'overdue_count'        => $overdueInvoices->count(),
                'staff_on_leave_count' => $staffOnLeave->count(),
                'reminders_today'      => $remindersToday->count(),
                'calls_today_count'    => $callsToday->count(),
            ],

            'meta' => [
                'generated_at' => $now->toIso8601String(),
                'timezone'     => config('app.timezone'),
                'workspace_id' => $workspaceId,
            ],
        ]);
    }
}
