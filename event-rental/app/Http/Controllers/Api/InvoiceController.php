<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    private function getWorkspaceId()
    {
        return auth()->user()->currentWorkspaceRecord()->id;
    }

    public function index()
    {
        return Invoice::with(['contact', 'items'])
            ->where('workspace_id', $this->getWorkspaceId())
            ->paginate(30);
    }

    public function store(Request $request)
    {
        $workspaceId = $this->getWorkspaceId();

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price_net' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $invoice = $this->invoiceService->createInvoice($validated, $workspaceId);

        return response()->json($invoice->load('items', 'contact'), 201);
    }

    public function show($id)
    {
        return Invoice::with(['contact', 'items', 'vatBreakdown', 'payments', 'attachments'])
            ->where('workspace_id', $this->getWorkspaceId())
            ->findOrFail($id);
    }

    public function destroy($id)
    {
        $invoice = Invoice::where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
        $invoice->delete();
        return response()->json(null, 204);
    }
}
