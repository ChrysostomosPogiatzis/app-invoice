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
}
