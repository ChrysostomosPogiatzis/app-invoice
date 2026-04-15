<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    use ResolvesWorkspace;

    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $workspaceId = $this->currentWorkspaceId();

        if ($invoice->workspace_id !== $workspaceId) {
            abort(403);
        }

        // Create the payment record
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'payment_date' => $validated['payment_date'],
            'reference' => $validated['notes'],
        ]);

        // Update the invoice totals
        $invoice->amount_paid += $validated['amount'];
        $invoice->balance_due = max(0, $invoice->grand_total_gross - $invoice->amount_paid);

        if ($invoice->balance_due <= 0.01) {
            $invoice->status = 'paid';
        } elseif ($invoice->amount_paid > 0) {
            $invoice->status = 'partial';
        }

        $invoice->save();

        return back()->with('success', 'Payment recorded successfully!');
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $workspaceId = $this->currentWorkspaceId();
        $payments = \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('workspace_id', $workspaceId))
            ->with(['invoice' => fn($q) => $q->with('contact:id,name,company_name')])
            ->orderByDesc('payment_date')
            ->paginate(50);

        return \Inertia\Inertia::render('Finance/Payments/Index', [
            'payments' => $payments,
        ]);
    }

    public function destroy($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $payment = \App\Models\Payment::whereHas('invoice', fn($q) => $q->where('workspace_id', $workspaceId))
            ->findOrFail($id);

        $invoice = $payment->invoice;
        $invoice->amount_paid = max(0, $invoice->amount_paid - $payment->amount);
        $invoice->balance_due = max(0, $invoice->grand_total_gross - $invoice->amount_paid);
        $invoice->status = $invoice->amount_paid <= 0 ? 'unpaid'
            : ($invoice->balance_due <= 0.01 ? 'paid' : 'partial');
        $invoice->save();

        $payment->delete();

        return back()->with('success', 'Payment deleted and invoice balance restored.');
    }

}
