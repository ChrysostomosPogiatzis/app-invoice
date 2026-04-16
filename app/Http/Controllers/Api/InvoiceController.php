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

    /**
     * @OA\Get(path="/api/invoices", tags={"Invoices"}, summary="List invoices (paginated)",
     *     security={{"BearerToken":{}}},
     *     @OA\Response(response=200, description="Paginated invoice list",
     *         @OA\JsonContent(@OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Invoice")),
     *             @OA\Property(property="meta", ref="#/components/schemas/PaginationMeta"))
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
    public function index()
    {
        return Invoice::with(['contact', 'items'])
            ->where('workspace_id', $this->getWorkspaceId())
            ->paginate(30);
    }

    /**
     * @OA\Post(path="/api/invoices", tags={"Invoices"}, summary="Create a new invoice",
     *     security={{"BearerToken":{}}},
     *     @OA\RequestBody(required=true,
     *         @OA\JsonContent(required={"contact_id","date","items"},
     *             @OA\Property(property="contact_id", type="integer", example=1),
     *             @OA\Property(property="date", type="string", format="date", example="2026-04-15"),
     *             @OA\Property(property="due_date", type="string", format="date", nullable=true),
     *             @OA\Property(property="discount", type="number", format="float", nullable=true, example=0),
     *             @OA\Property(property="notes", type="string", nullable=true),
     *             @OA\Property(property="items", type="array", @OA\Items(ref="#/components/schemas/InvoiceItem"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Invoice created", @OA\JsonContent(ref="#/components/schemas/Invoice")),
     *     @OA\Response(response=422, description="Validation error", @OA\JsonContent(ref="#/components/schemas/ErrorValidation")),
     *     @OA\Response(response=401, description="Unauthenticated", @OA\JsonContent(ref="#/components/schemas/Error"))
     * )
     */
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

    public function download($id)
    {
        $invoice = Invoice::with(['contact', 'items', 'workspace', 'payments'])->where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
        $amountInWords = $this->numberToWords($invoice->grand_total_gross);
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', compact('invoice', 'amountInWords'));
        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }

    private function numberToWords($number)
    {
        $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number)) . " Euro Only";
    }
}
