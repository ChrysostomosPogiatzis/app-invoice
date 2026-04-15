<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    use ResolvesWorkspace;

    public function __construct(private InvoiceService $invoiceService) {}

    /**
     * Display a listing of invoices.
     */
    public function index(Request $request)
    {
        $workspaceId = $this->currentWorkspaceId();
        $query = Invoice::with(['contact'])->where('workspace_id', $workspaceId);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('invoice_number', 'like', "%{$request->search}%")
                    ->orWhereHas('contact', function ($cq) use ($request) {
                        $cq->where('name', 'like', "%{$request->search}%")
                            ->orWhere('company_name', 'like', "%{$request->search}%");
                    });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['invoice_number', 'date', 'due_date', 'grand_total_gross', 'status', 'balance_due'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('date', 'desc');
        }

        return Inertia::render('Finance/Invoices', [
            'invoices' => $query->paginate(24)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'sort', 'direction'])
        ]);
    }

    /**
     * Display the specified invoice.
     */
    public function show($id)
    {
        $workspaceId = $this->currentWorkspaceId();

        $invoice = Invoice::with(['contact', 'items.product', 'payments', 'publicShares', 'workspace', 'attachments'])
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        return Inertia::render('Finance/InvoiceShow', [
            'invoice' => $invoice
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create()
    {
        $workspace = $this->currentWorkspace();
        $workspaceId = $workspace->id;
        $products = \App\Models\Product::where('workspace_id', $workspaceId)->orderBy('name')->get();

        return Inertia::render('Finance/InvoiceCreate', [
            'contacts' => \App\Models\Contact::where('workspace_id', $workspaceId)->orderBy('name')->get(),
            'products' => $products,
            'workspace' => $workspace
        ]);
    }

    /**
     * Store a newly created invoice in storage.
     */
    public function store(Request $request)
    {
        $workspaceId = $this->currentWorkspaceId();

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:date',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price_net' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'required|numeric|min:0',
            'attachment_files' => 'nullable|array',
            'attachment_files.*' => 'file|max:10240',
            'notes' => 'nullable|string',
        ]);

        $files = $request->hasFile('attachment_files') ? $request->file('attachment_files') : [];

        $invoice = $this->invoiceService->createInvoice($validated, $workspaceId, $files);

        return redirect()->route('invoices.show', $invoice->id)->with('success', 'Document issued and synchronized.');
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $invoice = Invoice::where('workspace_id', $workspaceId)->findOrFail($id);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Document voided and removed.');
    }

    /**
     * Download the specified invoice as PDF.
     */
    public function download($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $invoice = Invoice::with(['contact', 'items', 'workspace'])->where('workspace_id', $workspaceId)->findOrFail($id);

        $amountInWords = $this->numberToWords($invoice->grand_total_gross);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', compact('invoice', 'amountInWords'));
        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }


    /**
     * Send invoice to client by email.
     */
    public function sendEmail(\Illuminate\Http\Request $request, $id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $invoice = \App\Models\Invoice::with(['contact', 'items', 'workspace'])
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        if (!$invoice->contact || !$invoice->contact->email) {
            return back()->with('error', 'This contact has no email address on record.');
        }

        // Generate PDF
        $amountInWords = $this->numberToWords($invoice->grand_total_gross);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', compact('invoice', 'amountInWords'));
        $pdfContent = $pdf->output();

        // Send email with invoice attached
        \Illuminate\Support\Facades\Mail::send(
            'emails.invoice',
            ['invoice' => $invoice],
            function ($message) use ($invoice, $pdfContent) {
                $message->to($invoice->contact->email, $invoice->contact->name)
                    ->subject('Invoice ' . $invoice->invoice_number . ' from ' . ($invoice->workspace->company_name ?? 'Us'))
                    ->attachData($pdfContent, 'Invoice-' . $invoice->invoice_number . '.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            }
        );

        return back()->with('success', 'Invoice emailed to ' . $invoice->contact->email . ' successfully.');
    }


    /**
     * Mark an invoice as void.
     */
    public function void($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $invoice = Invoice::where('workspace_id', $workspaceId)->findOrFail($id);
        
        $this->invoiceService->voidInvoice($invoice);
        
        return back()->with('success', 'Document marked as void and stock adjusted.');
    }

    /**
     * Create a credit note based on an existing invoice.
     */
    public function createCreditNote($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $invoice = Invoice::with('items')->where('workspace_id', $workspaceId)->findOrFail($id);
        
        // Transform items for the service
        $items = $invoice->items->map(fn($item) => [
            'product_id' => $item->product_id,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price_net' => $item->unit_price_net,
            'vat_rate' => $item->vat_rate,
        ])->toArray();

        $data = [
            'contact_id' => $invoice->contact_id,
            'date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'items' => $items,
            'doc_type' => 'credit_note',
            'parent_id' => $invoice->id,
            'notes' => "Reversal for #{$invoice->invoice_number}",
        ];

        $creditNote = $this->invoiceService->createInvoice($data, $workspaceId);

        return redirect()->route('invoices.show', $creditNote->id)->with('success', 'Credit note issued.');
    }

    private function numberToWords($number)
    {
        $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number)) . " Euro Only";
    }
}
