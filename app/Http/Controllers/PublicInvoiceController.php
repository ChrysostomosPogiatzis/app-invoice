<?php

namespace App\Http\Controllers;

use App\Models\PublicShare;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PublicInvoiceController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->auditLog = $auditLog;
    }

    public function show(Request $request, $token)
    {
        $share = $this->resolveActiveShare($token);

        if ($share->requiresPassword() && ! $this->hasAuthorizedSession($request, $share)) {
            return Inertia::render('Public/InvoiceView', [
                'invoice' => null,
                'token' => $token,
                'requires_password' => true,
            ]);
        }

        $share->increment('view_count');
        $share->update(['last_viewed_at' => now()]);

        return Inertia::render('Public/InvoiceView', [
            'invoice' => $share->invoice,
            'token' => $token,
            'requires_password' => false,
        ]);
    }

    public function authorizeAccess(Request $request, $token)
    {
        $share = $this->resolveActiveShare($token);

        if (! $share->requiresPassword()) {
            return redirect()->route('public.invoice.show', $token);
        }

        $validated = $request->validate([
            'password' => 'required|string',
        ]);

        if (! $this->passwordMatches($validated['password'], $share->password)) {
            return back()->with('error', 'Invalid share password.');
        }

        $request->session()->put($this->accessSessionKey($share), true);

        return redirect()->route('public.invoice.show', $token);
    }

    public function sign(Request $request, $token)
    {
        $share = $this->resolveActiveShare($token);

        if ($share->requiresPassword() && ! $this->hasAuthorizedSession($request, $share)) {
            abort(403, 'Password verification is required for this shared document.');
        }

        $validated = $request->validate([
            'signature_base64' => 'required|string',
            'customer_name' => 'required|string',
        ]);

        $invoice = $share->invoice;

        if ($invoice->customer_signature_png || $invoice->signature_timestamp) {
            return back()->with('error', 'This document has already been signed.');
        }

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

    protected function resolveActiveShare(string $token): PublicShare
    {
        $share = PublicShare::where('share_token', $token)
            ->with('invoice.items', 'invoice.vatBreakdown', 'invoice.contact')
            ->firstOrFail();

        abort_if($share->isExpired(), 410, 'This shared document link has expired.');

        return $share;
    }

    protected function accessSessionKey(PublicShare $share): string
    {
        return 'public_share_access.' . $share->share_token;
    }

    protected function hasAuthorizedSession(Request $request, PublicShare $share): bool
    {
        return (bool) $request->session()->get($this->accessSessionKey($share), false);
    }

    protected function passwordMatches(string $plainText, string $stored): bool
    {
        $isHash = str_starts_with($stored, '$2y$')
            || str_starts_with($stored, '$2a$')
            || str_starts_with($stored, '$argon2');

        return $isHash ? Hash::check($plainText, $stored) : hash_equals($stored, $plainText);
    }
}
