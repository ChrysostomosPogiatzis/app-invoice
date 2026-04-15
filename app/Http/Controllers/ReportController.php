<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesStoredFile;
use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Invoice;
use App\Models\Expense;
use App\Models\BankTransaction;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use ZipArchive;

class ReportController extends Controller
{
    use ResolvesWorkspace, ResolvesStoredFile;

    /**
     * Display the reporting dashboard.
     */
    public function index()
    {
        $workspaceId = $this->currentWorkspaceId();

        return Inertia::render('Reports/Index', [
            'overview' => [
                'totalBilled'     => round(Invoice::where('workspace_id', $workspaceId)->where('status', '!=', 'void')->sum('grand_total_gross'), 2),
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
        $workspaceId = $this->currentWorkspaceId();
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

        $workspaceId = $this->currentWorkspaceId();
        $workspace   = $this->currentWorkspace();

        [$year, $month] = explode('-', $request->month);
        $monthLabel = \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y');

        $invoices = Invoice::with('contact')
            ->where('workspace_id', $workspaceId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        $byStatus = Invoice::where('workspace_id', $workspaceId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->selectRaw('status, SUM(subtotal_net) as net_total, SUM(total_vat_amount) as vat_total, SUM(grand_total_gross) as gross_total, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('gross_total', 'desc')
            ->get();

        $totalNet     = $invoices->sum('subtotal_net');
        $totalVat     = $invoices->sum('total_vat_amount');
        $totalGross   = $invoices->sum('grand_total_gross');
        $totalPaid    = $invoices->sum('amount_paid');
        $totalBalance = $invoices->sum('balance_due');

        $pdf = Pdf::loadView('pdf.monthly-invoices', compact(
            'invoices', 'workspace', 'monthLabel',
            'totalNet', 'totalVat', 'totalGross', 'totalPaid', 'totalBalance', 'byStatus'
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

        $workspaceId = $this->currentWorkspaceId();
        $workspace   = $this->currentWorkspace();

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

        $pdf = Pdf::loadView('pdf.monthly-expenses', compact(
            'expenses', 'workspace', 'monthLabel',
            'totalNet', 'totalVat', 'totalGross', 'byCategory'
        ))->setPaper('a4', 'portrait');
 
        return $pdf->download("Expenses-{$year}-{$month}.pdf");
    }
 
    /**
     * Download a monthly payroll liability PDF report.
     */
    public function monthlyPayrollPdf(Request $request)
    {
        $request->validate([
            'month' => 'required|date_format:Y-m',
        ]);

        $workspaceId = $this->currentWorkspaceId();
        $workspace   = $this->currentWorkspace();

        [$year, $month] = explode('-', $request->month);
        $monthLabel = \Carbon\Carbon::createFromDate($year, $month, 1)->format('F Y');

        $expenses = Expense::where('workspace_id', $workspaceId)
            ->where('is_payroll', true)
            ->whereYear('expense_date', $year)
            ->whereMonth('expense_date', $month)
            ->orderBy('expense_date')
            ->get();

        $siTotal   = $expenses->sum(fn ($e) => ($e->si_employee ?? 0) + ($e->si_employer ?? 0) + ($e->cohesion_amount ?? 0) + ($e->redundancy_amount ?? 0) + ($e->training_amount ?? 0));
        $gesiTotal = $expenses->sum(fn ($e) => ($e->gesi_employee ?? 0) + ($e->gesi_employer ?? 0));
        $providentTotal = $expenses->sum(fn ($e) => ($e->provident_employee ?? 0) + ($e->provident_employer ?? 0));
        $otherTotal = $expenses->sum(fn ($e) => ($e->holiday_amount ?? 0));
        $taxTotal  = $expenses->sum(fn ($e) => ($e->tax_employee ?? 0));
        $unionTotal = $expenses->sum(fn ($e) => ($e->union_amount ?? 0));

        $holidayTotal = $otherTotal; // Alias for blade template

        $pdf = Pdf::loadView('pdf.monthly-payroll', compact(
            'expenses', 'workspace', 'monthLabel',
            'siTotal', 'gesiTotal', 'providentTotal', 'otherTotal', 'taxTotal', 'unionTotal', 'holidayTotal'
        ))->setPaper('a4', 'portrait');

        return $pdf->download("Payroll-Liability-{$year}-{$month}.pdf");
    }

    /**
     * CPA/Accountant Export — generates a ZIP with all invoices, expense summary and bank summary.
     */
    public function accountantExport(Request $request)
    {
        $request->validate([
            'date_from' => 'required|date',
            'date_to'   => 'required|date|after_or_equal:date_from',
        ]);
 
        $workspaceId = $this->currentWorkspaceId();
        $workspace   = $this->currentWorkspace();
        $dateFrom    = $request->date_from;
        $dateTo      = $request->date_to;
 
        $invoices = Invoice::with(['contact', 'items', 'workspace'])
            ->where('workspace_id', $workspaceId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->get();
 
        $expenses = Expense::where('workspace_id', $workspaceId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->get();
 
        $transactions = BankTransaction::with('connection')
            ->where('workspace_id', $workspaceId)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->get();
 
        $byStatus = Invoice::where('workspace_id', $workspaceId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->selectRaw('status, SUM(subtotal_net) as net_total, SUM(total_vat_amount) as vat_total, SUM(grand_total_gross) as gross_total, COUNT(*) as count')
            ->groupBy('status')
            ->orderBy('gross_total', 'desc')
            ->get();
 
        $zipFileName = "Tax_Report_" . now()->format('Ymd_His') . ".zip";
        $zipPath = storage_path("app/public/{$zipFileName}");
 
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'Could not create ZIP file.');
        }
 
        // 1. Individual Invoice PDFs
        foreach ($invoices as $inv) {
            $amountInWords = $this->numberToWords($inv->grand_total_gross);
            $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $inv, 'amountInWords' => $amountInWords]);
            $zip->addFromString("Invoices/Document_{$inv->invoice_number}.pdf", $pdf->output());
        }
 
        // 2. Comprehensive Summaries
        $monthLabel = "Report from {$dateFrom} to {$dateTo}";
 
        // Invoice Summary
        $totalNet     = $invoices->sum('subtotal_net');
        $totalVat     = $invoices->sum('total_vat_amount');
        $totalGross   = $invoices->sum('grand_total_gross');
        $totalPaid    = $invoices->sum('amount_paid');
        $totalBalance = $invoices->sum('balance_due');
        $pdfInvSummary = Pdf::loadView('pdf.monthly-invoices', compact(
            'invoices', 'workspace', 'monthLabel',
            'totalNet', 'totalVat', 'totalGross', 'totalPaid', 'totalBalance', 'byStatus'
        ))->setPaper('a4', 'landscape');
        $zip->addFromString("Summary_Incoming_Invoices.pdf", $pdfInvSummary->output());
 
        // Business Expense Summary
        $totalNet   = $expenses->sum('amount');
        $totalVat   = $expenses->sum('vat_amount');
        $totalGrossArray = $expenses->map(fn ($e) => 
            (float)$e->amount + 
            (float)($e->vat_amount ?? 0) + 
            (float)($e->si_employer ?? 0) + 
            (float)($e->gesi_employer ?? 0) + 
            (float)($e->provident_employer ?? 0) +
            (float)($e->redundancy_amount ?? 0) +
            (float)($e->training_amount ?? 0) +
            (float)($e->cohesion_amount ?? 0) +
            (float)($e->holiday_amount ?? 0)
        );
        $totalGross = $totalGrossArray->sum();
        
        $byCategory = Expense::where('workspace_id', $workspaceId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->selectRaw('category, SUM(amount) as net_total, SUM(COALESCE(vat_amount,0)) as vat_total, SUM(amount + COALESCE(vat_amount,0) + COALESCE(si_employer,0) + COALESCE(gesi_employer,0) + COALESCE(provident_employer,0) + COALESCE(redundancy_amount,0) + COALESCE(training_amount,0) + COALESCE(cohesion_amount,0) + COALESCE(holiday_amount,0)) as gross_total')
            ->groupBy('category')
            ->orderByDesc('gross_total')
            ->get();
        $pdfExpSummary = Pdf::loadView('pdf.monthly-expenses', compact(
            'expenses', 'workspace', 'monthLabel',
            'totalNet', 'totalVat', 'totalGross', 'byCategory'
        ))->setPaper('a4', 'portrait');
        $zip->addFromString("Summary_Business_Expenses.pdf", $pdfExpSummary->output());
 
        // 3. Payroll Liability Summary
        $payrollExpenses = $expenses->where('is_payroll', true);
        if ($payrollExpenses->count() > 0) {
            $siTotal   = $payrollExpenses->sum(fn ($e) => ($e->si_employee ?? 0) + ($e->si_employer ?? 0) + ($e->cohesion_amount ?? 0) + ($e->redundancy_amount ?? 0) + ($e->training_amount ?? 0));
            $gesiTotal = $payrollExpenses->sum(fn ($e) => ($e->gesi_employee ?? 0) + ($e->gesi_employer ?? 0));
            $providentTotal = $payrollExpenses->sum(fn ($e) => ($e->provident_employee ?? 0) + ($e->provident_employer ?? 0));
            $otherTotal = $payrollExpenses->sum(fn ($e) => ($e->holiday_amount ?? 0));
            $taxTotal  = $payrollExpenses->sum(fn ($e) => ($e->tax_employee ?? 0));
            $unionTotal = $payrollExpenses->sum(fn ($e) => ($e->union_amount ?? 0));
            $expenses  = $payrollExpenses; // Re-use the variable for the PDF template logic
            
            $holidayTotal = $otherTotal; // Alias for blade template
            $pdfPayroll = Pdf::loadView('pdf.monthly-payroll', [
                'expenses'      => $payrollExpenses, 
                'workspace'     => $workspace, 
                'monthLabel'    => $monthLabel,
                'siTotal'       => $siTotal, 
                'gesiTotal'     => $gesiTotal, 
                'providentTotal' => $providentTotal,
                'otherTotal'    => $otherTotal,
                'holidayTotal'  => $holidayTotal,
                'taxTotal'      => $taxTotal,
                'unionTotal'    => $unionTotal
            ])->setPaper('a4', 'portrait');
            $zip->addFromString("Summary_Payroll_Liabilities.pdf", $pdfPayroll->output());
        }

        // 4. Original Expense Receipt Files
        foreach ($expenses as $expense) {
            if ($expense->receipt_url) {
                $fullPath = $this->storedFileAbsolutePath($expense->receipt_url);
                if ($fullPath && file_exists($fullPath)) {
                    $ext = pathinfo($fullPath, PATHINFO_EXTENSION);
                    $dateLabel = \Carbon\Carbon::parse($expense->expense_date)->format('Ymd');
                    $zip->addFile($fullPath, "Expenses_Receipts/Exp_{$dateLabel}_{$expense->id}.{$ext}");
                }
            }
        }
 
        // 4. Bank Detail PDF
        $pdfBank = Pdf::loadView('pdf.bank-transactions', [
            'transactions' => $transactions,
            'workspace'    => $workspace,
            'monthLabel'   => $monthLabel
        ])->setPaper('a4', 'portrait');
        $zip->addFromString("Banking_Transactions_Detail.pdf", $pdfBank->output());
 
        $zip->close();
 
        return response()->download($zipPath)->deleteFileAfterSend(true);
    }
 
    private function numberToWords($number)
    {
        $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number)) . " Euro Only";
    }
}
