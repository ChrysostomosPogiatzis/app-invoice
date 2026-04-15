<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\Quote;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    use ResolvesWorkspace;

    /**
     * Display a listing of quotes for the current workspace.
     */
    public function index(Request $request)
    {
        $workspaceId = $this->currentWorkspaceId();
        $query = Quote::with(['contact'])->where('workspace_id', $workspaceId);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('quote_number', 'like', "%{$request->search}%")
                    ->orWhereHas('contact', function ($cq) use ($request) {
                        $cq->where('name', 'like', "%{$request->search}%")
                            ->orWhere('company_name', 'like', "%{$request->search}%");
                    });
            });
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Sorting
        $sortField = $request->get('sort', 'date');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['quote_number', 'date', 'valid_until', 'grand_total_gross', 'status'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        } else {
            $query->orderBy('date', 'desc');
        }

        return Inertia::render('Quotes/Index', [
            'quotes' => $query->paginate(24)->withQueryString(),
            'filters' => $request->only(['search', 'status', 'sort', 'direction'])
        ]);
    }

    /**
     * Show the form for creating a new quote.
     */
    public function create()
    {
        $workspace = $this->currentWorkspace();
        $workspaceId = $workspace->id;
        $products = \App\Models\Product::where('workspace_id', $workspaceId)->orderBy('name')->get();

        return Inertia::render('Quotes/Create', [
            'contacts' => \App\Models\Contact::where('workspace_id', $workspaceId)->orderBy('name')->get(),
            'products' => $products,
            'workspace' => $workspace
        ]);
    }

    /**
     * Store a newly created quote in storage.
     */
    public function store(Request $request)
    {
        $workspaceId = $this->currentWorkspaceId();

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:date',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price_net' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $workspaceId, $request) {
            $workspace = \App\Models\Workspace::findOrFail($workspaceId);
            $prefix = $workspace->quote_prefix ?? 'Q-';
            $quoteNumber = $prefix . str_pad($workspace->next_quote_number, 4, '0', STR_PAD_LEFT);

            // Advance serial
            $workspace->increment('next_quote_number');

            $subtotalNet = 0;
            $totalVat = 0;
            $discount = $validated['discount'] ?? 0;

            foreach ($validated['items'] as $item) {
                $itemNet = $item['quantity'] * $item['unit_price_net'];
                $itemVat = $itemNet * ($item['vat_rate'] / 100);
                $subtotalNet += $itemNet;
                $totalVat += $itemVat;
            }

            $grandTotal = round(($subtotalNet + $totalVat) - $discount, 2);

            $quote = Quote::create([
                'workspace_id' => $workspaceId,
                'contact_id' => $validated['contact_id'],
                'quote_number' => $quoteNumber,
                'date' => $validated['date'],
                'valid_until' => $validated['valid_until'],
                'status' => 'draft',
                'discount' => $discount,
                'subtotal_net' => round($subtotalNet, 2),
                'total_vat_amount' => round($totalVat, 2),
                'grand_total_gross' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $itemNet = $item['quantity'] * $item['unit_price_net'];
                $itemVat = $itemNet * ($item['vat_rate'] / 100);

                $quote->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price_net' => $item['unit_price_net'],
                    'vat_rate' => $item['vat_rate'],
                    'total_gross' => round($itemNet + $itemVat, 2)
                ]);
            }

            return redirect()->route('quotes.show', $quote->id)->with('success', 'Quote created successfully.');
        });
    }

    /**
     * Display the specified quote.
     */
    public function show($id)
    {
        $workspaceId = $this->currentWorkspaceId();

        $quote = Quote::with(['contact', 'items.product', 'invoice', 'workspace', 'attachments'])
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        return Inertia::render('Quotes/Show', [
            'quote' => $quote
        ]);
    }

    /**
     * Show the form for editing the specified quote.
     */
    public function edit($id)
    {
        $workspaceId = $this->currentWorkspaceId();

        $quote = Quote::with('items')
            ->where('workspace_id', $workspaceId)
            ->findOrFail($id);

        // Only allow editing drafts
        if ($quote->status !== 'draft') {
            return redirect()->route('quotes.show', $quote->id)
                ->with('error', 'Only draft quotes can be edited.');
        }

        $workspace = $this->currentWorkspace();
        $products = \App\Models\Product::where('workspace_id', $workspaceId)->orderBy('name')->get();

        return Inertia::render('Quotes/Edit', [
            'quote' => $quote,
            'contacts' => \App\Models\Contact::where('workspace_id', $workspaceId)->orderBy('name')->get(),
            'products' => $products,
            'workspace' => $workspace
        ]);
    }

    /**
     * Update the specified quote in storage.
     */
    public function update(Request $request, $id)
    {
        $workspaceId = $this->currentWorkspaceId();

        $quote = Quote::where('workspace_id', $workspaceId)->findOrFail($id);

        // Only allow editing drafts
        if ($quote->status !== 'draft') {
            return redirect()->route('quotes.show', $quote->id)
                ->with('error', 'Only draft quotes can be edited.');
        }

        $validated = $request->validate([
            'contact_id' => 'required|exists:contacts,id',
            'date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:date',
            'discount' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price_net' => 'required|numeric|min:0',
            'items.*.vat_rate' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($validated, $quote, $request) {
            $subtotalNet = 0;
            $totalVat = 0;
            $discount = $validated['discount'] ?? 0;

            foreach ($validated['items'] as $item) {
                $itemNet = $item['quantity'] * $item['unit_price_net'];
                $itemVat = $itemNet * ($item['vat_rate'] / 100);
                $subtotalNet += $itemNet;
                $totalVat += $itemVat;
            }

            $grandTotal = round(($subtotalNet + $totalVat) - $discount, 2);

            $quote->update([
                'contact_id' => $validated['contact_id'],
                'date' => $validated['date'],
                'valid_until' => $validated['valid_until'],
                'discount' => $discount,
                'subtotal_net' => round($subtotalNet, 2),
                'total_vat_amount' => round($totalVat, 2),
                'grand_total_gross' => $grandTotal,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
            ]);

            // Delete old items and create new ones
            $quote->items()->delete();

            foreach ($validated['items'] as $item) {
                $itemNet = $item['quantity'] * $item['unit_price_net'];
                $itemVat = $itemNet * ($item['vat_rate'] / 100);

                $quote->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price_net' => $item['unit_price_net'],
                    'vat_rate' => $item['vat_rate'],
                    'total_gross' => round($itemNet + $itemVat, 2)
                ]);
            }

            return redirect()->route('quotes.show', $quote->id)->with('success', 'Quote updated successfully.');
        });
    }

    /**
     * Remove the specified quote.
     */
    public function destroy($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $quote = Quote::where('workspace_id', $workspaceId)->findOrFail($id);
        $quote->delete();

        return redirect()->route('quotes.index')->with('success', 'Quote deleted successfully.');
    }

    /**
     * Convert a quote to an invoice.
     */
    public function convertToInvoice($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $quote = Quote::with('items')->where('workspace_id', $workspaceId)->findOrFail($id);

        // Only allow converting drafts, sent, viewed, or accepted quotes
        if (in_array($quote->status, ['converted', 'declined', 'expired'])) {
            return redirect()->route('quotes.show', $quote->id)
                ->with('error', 'This quote cannot be converted to an invoice.');
        }

        return DB::transaction(function () use ($quote, $workspaceId) {
            $workspace = \App\Models\Workspace::findOrFail($workspaceId);
            $prefix = $workspace->invoice_prefix ?? '';
            $invoiceNumber = $prefix . $workspace->next_invoice_number;

            // Advance invoice serial
            $workspace->increment('next_invoice_number');

            $discount = $quote->discount ?? 0;

            $invoice = Invoice::create([
                'workspace_id' => $workspaceId,
                'contact_id' => $quote->contact_id,
                'invoice_number' => $invoiceNumber,
                'date' => now()->toDateString(),
                'due_date' => $quote->valid_until,
                'subtotal_net' => $quote->subtotal_net,
                'total_vat_amount' => $quote->total_vat_amount,
                'grand_total_gross' => $quote->grand_total_gross,
                'amount_paid' => 0.00,
                'balance_due' => $quote->grand_total_gross,
                'status' => 'unpaid',
                'doc_type' => 'invoice'
            ]);

            $vatTotals = [];
            foreach ($quote->items as $item) {
                // Update Inventory
                if (isset($item['product_id'])) {
                    $product = \App\Models\Product::find($item['product_id']);
                    if ($product) {
                        $product->decrement('current_stock', $item['quantity']);

                        // Log Movement
                        \App\Models\StockMovement::create([
                            'product_id' => $product->id,
                            'quantity' => $item['quantity'],
                            'direction' => 'out',
                            'movement_type' => 'sale',
                            'reference_id' => $invoice->id,
                            'notes' => "Sold via Invoice #{$invoiceNumber} (from Quote #{$quote->quote_number})"
                        ]);
                    }
                }

                $itemNet = $item['quantity'] * $item['unit_price_net'];
                $itemVat = $itemNet * ($item['vat_rate'] / 100);

                $rate = (string) $item['vat_rate'];
                if (!isset($vatTotals[$rate])) {
                    $vatTotals[$rate] = ['net' => 0, 'vat' => 0];
                }
                $vatTotals[$rate]['net'] += $itemNet;
                $vatTotals[$rate]['vat'] += $itemVat;

                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'description' => $item['description'],
                    'quantity' => $item['quantity'],
                    'unit_price_net' => $item['unit_price_net'],
                    'vat_rate' => $item['vat_rate'],
                    'total_gross' => $item['total_gross']
                ]);
            }

            foreach ($vatTotals as $rate => $amounts) {
                $invoice->vatBreakdown()->create([
                    'vat_rate' => (float) $rate,
                    'net_amount' => round($amounts['net'], 2),
                    'vat_amount' => round($amounts['vat'], 2),
                ]);
            }

            // Update quote status
            $quote->update([
                'status' => 'converted',
                'converted_to_invoice_id' => $invoice->id,
                'converted_at' => now(),
            ]);

            return redirect()->route('invoices.show', $invoice->id)->with('success', 'Quote converted to invoice successfully.');
        });
    }

    /**
     * Update the status of a quote.
     */
    public function updateStatus(Request $request, $id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $quote = Quote::where('workspace_id', $workspaceId)->findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:draft,sent,viewed,accepted,declined,expired',
        ]);

        $quote->update(['status' => $validated['status']]);

        return redirect()->route('quotes.show', $quote->id)->with('success', 'Quote status updated.');
    }

    /**
     * Download the specified quote as PDF.
     */
    public function download($id)
    {
        $workspaceId = $this->currentWorkspaceId();
        $quote = Quote::with(['contact', 'items', 'workspace'])->where('workspace_id', $workspaceId)->findOrFail($id);

        $amountInWords = $this->numberToWords($quote->grand_total_gross);

        // We can reuse invoice view but maybe with "Quotation" title
        // For now let's assume we have a pdf.quote view or just use invoice.blade with a flag
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', ['invoice' => $quote, 'amountInWords' => $amountInWords, 'isQuote' => true]);
        return $pdf->download("Quote-{$quote->quote_number}.pdf");
    }

    private function numberToWords($number)
    {
        $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number)) . " Euro Only";
    }
}
