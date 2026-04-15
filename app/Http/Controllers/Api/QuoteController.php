<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    private function getWorkspaceId()
    {
        return auth()->user()->currentWorkspaceRecord()->id;
    }

    public function index()
    {
        return Quote::with(['contact', 'items'])
            ->where('workspace_id', $this->getWorkspaceId())
            ->orderBy('date', 'desc')
            ->paginate(30);
    }

    public function show($id)
    {
        return Quote::with(['contact', 'items', 'invoice', 'attachments'])
            ->where('workspace_id', $this->getWorkspaceId())
            ->findOrFail($id);
    }

    public function store(Request $request)
    {
        $workspaceId = $this->getWorkspaceId();

        $validated = $request->validate([
            'contact_id'            => 'required|exists:contacts,id',
            'date'                  => 'required|date',
            'valid_until'           => 'required|date|after_or_equal:date',
            'discount'              => 'nullable|numeric|min:0',
            'notes'                 => 'nullable|string',
            'terms'                 => 'nullable|string',
            'items'                 => 'required|array|min:1',
            'items.*.product_id'    => 'nullable|exists:products,id',
            'items.*.description'   => 'required|string',
            'items.*.quantity'      => 'required|numeric|min:0.01',
            'items.*.unit_price_net'=> 'required|numeric|min:0',
            'items.*.vat_rate'      => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated, $workspaceId) {
            $workspace   = Workspace::findOrFail($workspaceId);
            $prefix      = $workspace->quote_prefix ?? 'Q-';
            $quoteNumber = $prefix . str_pad($workspace->next_quote_number, 4, '0', STR_PAD_LEFT);
            $workspace->increment('next_quote_number');

            $subtotalNet = 0;
            $totalVat    = 0;
            $discount    = $validated['discount'] ?? 0;

            foreach ($validated['items'] as $item) {
                $itemNet = $item['quantity'] * $item['unit_price_net'];
                $itemVat = $itemNet * ($item['vat_rate'] / 100);
                $subtotalNet += $itemNet;
                $totalVat    += $itemVat;
            }

            $grandTotal = round(($subtotalNet + $totalVat) - $discount, 2);

            $quote = Quote::create([
                'workspace_id'     => $workspaceId,
                'contact_id'       => $validated['contact_id'],
                'quote_number'     => $quoteNumber,
                'date'             => $validated['date'],
                'valid_until'      => $validated['valid_until'],
                'status'           => 'draft',
                'discount'         => $discount,
                'subtotal_net'     => round($subtotalNet, 2),
                'total_vat_amount' => round($totalVat, 2),
                'grand_total_gross'=> $grandTotal,
                'notes'            => $validated['notes'] ?? null,
                'terms'            => $validated['terms'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                $itemNet = $item['quantity'] * $item['unit_price_net'];
                $itemVat = $itemNet * ($item['vat_rate'] / 100);

                $quote->items()->create([
                    'product_id'     => $item['product_id'] ?? null,
                    'description'    => $item['description'],
                    'quantity'       => $item['quantity'],
                    'unit_price_net' => $item['unit_price_net'],
                    'vat_rate'       => $item['vat_rate'],
                    'total_gross'    => round($itemNet + $itemVat, 2),
                ]);
            }

            return response()->json($quote->load(['contact', 'items']), 201);
        });
    }

    public function download($id)
    {
        $quote         = Quote::with(['contact', 'items', 'workspace'])->where('workspace_id', $this->getWorkspaceId())->findOrFail($id);
        $amountInWords = $this->numberToWords($quote->grand_total_gross);
        $pdf           = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', ['invoice' => $quote, 'amountInWords' => $amountInWords, 'isQuote' => true]);
        return $pdf->download("Quote-{$quote->quote_number}.pdf");
    }

    private function numberToWords($number)
    {
        $f = new \NumberFormatter("en", \NumberFormatter::SPELLOUT);
        return ucfirst($f->format($number)) . " Euro Only";
    }
}
