<?php

namespace App\Http\Controllers;

use App\Models\PublicShare;
use App\Models\Invoice;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class PublicInvoiceController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    public function show($token)
    {
        $share = PublicShare::where('share_token', $token)->with('invoice.items', 'invoice.vatBreakdown', 'invoice.contact')->firstOrFail();

        $share->increment('view_count');
        $share->update(['last_viewed_at' => now()]);

        return Inertia::render('Public/InvoiceView', [
            'invoice' => $share->invoice,
            'token' => $token
        ]);
    }

    public function sign(Request $request, $token)
    {
        $validated = $request->validate([
            'signature_base64' => 'required|string',
            'customer_name' => 'required|string',
        ]);

        $share = PublicShare::where('share_token', $token)->with('invoice')->firstOrFail();
        $invoice = $share->invoice;

        return DB::transaction(function () use ($invoice, $validated, $request) {
            $invoice->update([
                'customer_signature_png' => $validated['signature_base64'],
                'customer_signature_name' => $validated['customer_name'],
                'signature_timestamp' => now(),
                'signature_ip' => $request->ip(),
            ]);

            $this->auditLog->log('SIGN', 'Invoice', $invoice->id, null, ['signed_by' => $validated['customer_name']], $invoice->workspace_id);

            return back()->with('success', 'Document signed successfully!');
        });
    }
}
