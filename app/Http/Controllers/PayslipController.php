<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Expense;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class PayslipController extends Controller
{
    use ResolvesWorkspace;

    public function download($expenseId)
    {
        $workspaceId = $this->currentWorkspaceId();
        $expense = Expense::where('workspace_id', $workspaceId)
            ->where('is_payroll', true)
            ->findOrFail($expenseId);

        $staff = $expense->staffMember;
        $workspace = $this->currentWorkspace();

        $pdf = Pdf::loadView('pdf.payslip', [
            'expense' => $expense,
            'staff' => $staff,
            'workspace' => $workspace,
            'date' => $expense->expense_date
        ]);

        $filename = 'payslip_' . $staff->name . '_' . $expense->expense_date . '.pdf';
        return $pdf->download($filename);
    }
}
