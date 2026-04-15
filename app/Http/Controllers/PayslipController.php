<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PayslipController extends Controller
{
    public function download($expenseId)
    {
        $workspaceId = Auth::user()->workspaces()->first()->id;
        $expense = Expense::where('workspace_id', $workspaceId)
            ->where('is_payroll', true)
            ->findOrFail($expenseId);

        $staff = $expense->staffMember;
        $workspace = Auth::user()->workspaces()->first();

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
