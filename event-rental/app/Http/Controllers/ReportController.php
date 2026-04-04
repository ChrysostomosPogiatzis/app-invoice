<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * Display the reporting dashboard.
     */
    public function index()
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;

        return Inertia::render('Reports/Index', [
            'overview' => [
                'totalBilled'     => round(Invoice::where('workspace_id', $workspaceId)->sum('grand_total_gross'), 2),
                'totalExpenses'   => round(Expense::where('workspace_id', $workspaceId)->sum('amount'), 2),
                'pendingPayments' => round(Invoice::where('workspace_id', $workspaceId)->where('status', 'unpaid')->sum('balance_due'), 2)
            ]
        ]);
    }

    /**
     * Export system data (CSV).
     */
    public function export(Request $request)
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;
        $type        = $request->query('type', 'financial');
        $fileName    = "{$type}_export_" . date('Ymd_His') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        return response()->streamDownload(function () use ($type, $workspaceId) {
            $file = fopen('php://output', 'w');

            if ($type === 'financial') {
                fputcsv($file, ['Invoice Number', 'Document Date', 'Client Name', 'Status', 'Net Amount', 'VAT', 'Gross Total']);
                $invoices = Invoice::with('contact')->where('workspace_id', $workspaceId)->get();
                foreach ($invoices as $inv) {
                    fputcsv($file, [
                        $inv->invoice_number,
                        $inv->date,
                        $inv->contact->name ?? 'N/A',
                        strtoupper($inv->status),
                        round($inv->subtotal_net, 2),
                        round($inv->total_vat_amount, 2),
                        round($inv->grand_total_gross, 2)
                    ]);
                }
            } elseif ($type === 'inventory') {
                fputcsv($file, ['SKU/Code', 'Asset Name', 'Unit Price', 'Total Stock', 'Asset Value']);
                $products = \App\Models\Product::where('workspace_id', $workspaceId)->get();
                foreach ($products as $prod) {
                    fputcsv($file, [
                        $prod->sku,
                        $prod->name,
                        $prod->purchase_price,
                        $prod->current_stock,
                        ($prod->purchase_price * $prod->current_stock)
                    ]);
                }
            } elseif ($type === 'clients') {
                fputcsv($file, ['Client Name', 'Company', 'Email', 'Phone', 'Role']);
                $contacts = \App\Models\Contact::where('workspace_id', $workspaceId)->get();
                foreach ($contacts as $contact) {
                    fputcsv($file, [
                        $contact->name,
                        $contact->company_name,
                        $contact->email,
                        $contact->mobile_number,
                        ucfirst($contact->contact_type)
                    ]);
                }
            }
            fclose($file);
        }, $fileName, $headers);
    }

    /**
     * Download a monthly invoices PDF report.
     */
    public function monthlyInvoicesPdf(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $workspaceId = Auth::user()->workspaces()->first()->id;
        $workspace   = \App\Models\Workspace::findOrFail($workspaceId);

        [$year, $month] = explode('-', $request->month);
        $monthLabel = \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y');

        $invoices = Invoice::with('contact')
            ->where('workspace_id', $workspaceId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        $totalNet     = $invoices->sum('subtotal_net');
        $totalVat     = $invoices->sum('total_vat_amount');
        $totalGross   = $invoices->sum('grand_total_gross');
        $totalPaid    = $invoices->sum('amount_paid');
        $totalBalance = $invoices->sum('balance_due');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monthly-invoices', compact(
            'invoices', 'workspace', 'monthLabel',
            'totalNet', 'totalVat', 'totalGross', 'totalPaid', 'totalBalance'
        ))->setPaper('a4', 'landscape');

        return $pdf->download("Invoices-{$year}-{$month}.pdf");
    }

    /**
     * Download a monthly expenses PDF report.
     */
    public function monthlyExpensesPdf(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $workspaceId = Auth::user()->workspaces()->first()->id;
        $workspace   = \App\Models\Workspace::findOrFail($workspaceId);

        [$year, $month] = explode('-', $request->month);
        $monthLabel = \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y');

        $expenses = Expense::where('workspace_id', $workspaceId)
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->orderBy('expense_date')
            ->get();

        $totalNet   = $expenses->sum('amount');
        $totalVat   = $expenses->sum('vat_amount');
        $totalGross = $expenses->sum(fn ($e) => $e->amount + ($e->vat_amount ?? 0));

        $byCategory = Expense::where('workspace_id', $workspaceId)
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->selectRaw('category, SUM(amount) as net_total, SUM(COALESCE(vat_amount,0)) as vat_total, SUM(amount + COALESCE(vat_amount,0)) as gross_total')
            ->groupBy('category')
            ->orderByDesc('gross_total')
            ->get();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.monthly-expenses', compact(
            'expenses', 'workspace', 'monthLabel',
            'totalNet', 'totalVat', 'totalGross', 'byCategory'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("Expenses-{$year}-{$month}.pdf");
    }
}
