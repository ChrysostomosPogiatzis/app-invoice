<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Workspace;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Attachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    public function createInvoice(array $validated, int $workspaceId, $files = [])
    {
        return DB::transaction(function () use ($validated, $workspaceId, $files) {
            $workspace = Workspace::findOrFail($workspaceId);
            $docType = $validated['doc_type'] ?? 'invoice';
            $prefix = $workspace->invoice_prefix ?? '';
            
            if ($docType === 'credit_note') {
                $prefix = 'CN-' . ($workspace->invoice_prefix ?? '');
            }
            
            $invoiceNumber = $prefix . $workspace->next_invoice_number;
            $workspace->increment('next_invoice_number');

            $subtotalNet = 0;
            $totalVat = 0;
            $discount = $validated['discount'] ?? 0;

            foreach ($validated['items'] as $item) {
                $itemNet = $item['quantity'] * ($item['unit_price_net'] ?? 0);
                $itemVat = $itemNet * (($item['vat_rate'] ?? 0) / 100);
                $subtotalNet += $itemNet;
                $totalVat += $itemVat;
            }

            $grandTotal = round(($subtotalNet + $totalVat) - $discount, 2);

            $invoice = Invoice::create([
                'workspace_id'      => $workspaceId,
                'contact_id'        => $validated['contact_id'],
                'invoice_number'    => $invoiceNumber,
                'date'              => $validated['date'],
                'due_date'          => $validated['due_date'] ?? $validated['date'],
                'subtotal_net'      => round($subtotalNet, 2),
                'total_vat_amount'  => round($totalVat, 2),
                'grand_total_gross' => $grandTotal,
                'amount_paid'       => 0.00,
                'balance_due'       => $grandTotal,
                'status'            => 'unpaid',
                'doc_type'          => $docType,
                'parent_id'         => $validated['parent_id'] ?? null,
                'notes'             => $validated['notes'] ?? null,
                'terms'             => $workspace->default_invoice_notes,
            ]);

            $vatTotals = [];
            foreach ($validated['items'] as $item) {
                if (isset($item['product_id'])) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        if ($docType === 'credit_note') {
                            $product->increment('current_stock', $item['quantity']);
                        } else {
                            $product->decrement('current_stock', $item['quantity']);
                        }

                        StockMovement::create([
                            'product_id'    => $product->id,
                            'quantity'      => $item['quantity'],
                            'direction'     => $docType === 'credit_note' ? 'in' : 'out',
                            'movement_type' => $docType === 'credit_note' ? 'return' : 'sale',
                            'reference_id'  => $invoice->id,
                            'notes'         => ($docType === 'credit_note' ? "Returned via Credit Note #" : "Sold via Invoice #") . $invoiceNumber
                        ]);
                    }
                }

                $itemNet = $item['quantity'] * ($item['unit_price_net'] ?? 0);
                $itemVat = $itemNet * (($item['vat_rate'] ?? 0) / 100);
                $rate    = (string) ($item['vat_rate'] ?? 0);

                if (!isset($vatTotals[$rate])) {
                    $vatTotals[$rate] = ['net' => 0, 'vat' => 0];
                }
                $vatTotals[$rate]['net'] += $itemNet;
                $vatTotals[$rate]['vat'] += $itemVat;

                $invoice->items()->create([
                    'product_id'     => $item['product_id'] ?? null,
                    'description'    => $item['description'],
                    'quantity'       => $item['quantity'],
                    'unit_price_net' => $item['unit_price_net'] ?? 0,
                    'vat_rate'       => $item['vat_rate'] ?? 0,
                    'total_gross'    => round($itemNet + $itemVat, 2)
                ]);
            }

            foreach ($vatTotals as $rate => $amounts) {
                $invoice->vatBreakdown()->create([
                    'vat_rate'   => (float) $rate,
                    'net_amount' => round($amounts['net'], 2),
                    'vat_amount' => round($amounts['vat'], 2),
                ]);
            }

            if (!empty($files)) {
                foreach ($files as $file) {
                    $path = $file->store('attachments', 'local');
                    Attachment::create([
                        'related_id'   => $invoice->id,
                        'related_type' => 'invoice',
                        'file_url'     => $path,
                        'file_name'    => $file->getClientOriginalName(),
                    ]);
                }
            }

            return $invoice;
        });
    }

    public function voidInvoice(Invoice $invoice)
    {
        return DB::transaction(function () use ($invoice) {
            if ($invoice->status === 'void') {
                return $invoice;
            }

            foreach ($invoice->items as $item) {
                if ($item->product_id) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        if ($invoice->doc_type === 'credit_note') {
                            $product->decrement('current_stock', $item->quantity);
                        } else {
                            $product->increment('current_stock', $item->quantity);
                        }

                        StockMovement::create([
                            'product_id'    => $product->id,
                            'quantity'      => $item->quantity,
                            'direction'     => $invoice->doc_type === 'credit_note' ? 'out' : 'in',
                            'movement_type' => 'return',
                            'reference_id'  => $invoice->id,
                            'notes'         => "Voided " . ucfirst($invoice->doc_type) . " #" . $invoice->invoice_number
                        ]);
                    }
                }
            }

            $invoice->status      = 'void';
            $invoice->balance_due = 0;
            $invoice->save();

            return $invoice;
        });
    }
}
