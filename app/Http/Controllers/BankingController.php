<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\ResolvesWorkspace;
use App\Models\BankingConnection;
use App\Models\BankTransaction;
use App\Models\Invoice;
use App\Models\Expense;
use App\Services\Banking\VivaWalletService;
use App\Services\Banking\MyPosService;
use App\Services\Banking\EurobankService;
use App\Services\Banking\BankOfCyprusService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class BankingController extends Controller
{
    use ResolvesWorkspace;

    private function workspaceId(): int
    {
        return $this->currentWorkspaceId();
    }

    // ------------------------------------------------------------------ INDEX
    public function index(Request $request)
    {
        $wsId        = $this->workspaceId();
        $connections = BankingConnection::where('workspace_id', $wsId)
            ->withCount('transactions')
            ->latest()
            ->get()
            ->map(fn ($c) => [
                'id'              => $c->id,
                'provider'        => $c->provider,
                'label'           => $c->label,
                'is_active'       => $c->is_active,
                'last_synced_at'  => $c->last_synced_at?->diffForHumans(),
                'transactions_count' => $c->transactions_count,
                'accounts'        => $c->credential('last_known_accounts') ?? [],
            ]);

        // Transactions filter
        $connectionId = $request->get('connection_id');
        $dateFrom     = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo       = $request->get('date_to', now()->toDateString());

        $txQuery = BankTransaction::with(['connection', 'linked'])
            ->where('workspace_id', $wsId)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->latest('transaction_date');

        if ($connectionId) {
            $txQuery->where('banking_connection_id', $connectionId);
        }

        $transactions = $txQuery->paginate(50)->withQueryString();

        $totalVolume = BankTransaction::where('workspace_id', $wsId)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo . ' 23:59:59'])
            ->when($connectionId, fn ($q) => $q->where('banking_connection_id', $connectionId))
            ->sum('amount');

        // For the reconciliation picker — lightweight lists
        $invoices = Invoice::where('workspace_id', $wsId)
            ->select('id', 'invoice_number', 'grand_total_gross')
            ->latest('date')
            ->limit(200)
            ->get();

        $expenses = Expense::where('workspace_id', $wsId)
            ->select('id', 'vendor_name', 'expense_date', 'amount')
            ->latest('expense_date')
            ->limit(200)
            ->get();

        return Inertia::render('Banking/Index', [
            'connections'  => $connections,
            'transactions' => $transactions,
            'totalVolume'  => round($totalVolume, 2),
            'filters'      => compact('connectionId', 'dateFrom', 'dateTo'),
            'invoices'     => $invoices,
            'expenses'     => $expenses,
        ]);
    }

    // ------------------------------------------------------------ CREATE FORM
    public function create()
    {
        return Inertia::render('Banking/CreateConnection');
    }

    // ------------------------------------------------------------------ STORE
    public function store(Request $request)
    {
        $wsId = $this->workspaceId();

        $validated = $request->validate([
            'provider' => 'required|in:vivawallet,mypos,eurobank,boc',
            'label'    => 'required|string|max:100',
            // provider credentials come in as a flat object — we'll build the JSON
            'credentials' => 'required|array',
        ]);

        BankingConnection::create([
            'workspace_id' => $wsId,
            'provider'     => $validated['provider'],
            'label'        => $validated['label'],
            'credentials'  => $validated['credentials'], // setter encrypts it
        ]);

        return redirect()->route('banking.index')->with('success', 'Connection added successfully.');
    }

    // ------------------------------------------------------------------- EDIT
    public function edit($id)
    {
        $conn = BankingConnection::where('workspace_id', $this->workspaceId())->findOrFail($id);

        return Inertia::render('Banking/EditConnection', [
            'connection' => [
                'id'       => $conn->id,
                'provider' => $conn->provider,
                'label'    => $conn->label,
                'is_active'=> $conn->is_active,
                // Pass credentials back (decrypted) for editing — secrets shown masked in UI
                'credentials' => $conn->credentials,
            ],
        ]);
    }

    // ----------------------------------------------------------------- UPDATE
    public function update(Request $request, $id)
    {
        $conn = BankingConnection::where('workspace_id', $this->workspaceId())->findOrFail($id);

        $validated = $request->validate([
            'label'       => 'required|string|max:100',
            'is_active'   => 'boolean',
            'credentials' => 'required|array',
        ]);

        $conn->update([
            'label'       => $validated['label'],
            'is_active'   => $validated['is_active'] ?? $conn->is_active,
            'credentials' => array_merge($conn->credentials ?? [], $validated['credentials']),
        ]);

        return redirect()->route('banking.index')->with('success', 'Connection updated.');
    }

    // --------------------------------------------------------------- DESTROY
    public function destroy($id)
    {
        $conn = BankingConnection::where('workspace_id', $this->workspaceId())->findOrFail($id);
        $conn->delete();

        return redirect()->route('banking.index')->with('success', 'Connection removed.');
    }

    // ------------------------------------------------------------------- SYNC
    public function sync(Request $request, $id)
    {
        $conn = BankingConnection::where('workspace_id', $this->workspaceId())->findOrFail($id);

        $dateFrom = $request->get('date_from', now()->startOfMonth()->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());

        try {
            $service = match($conn->provider) {
                'vivawallet' => new VivaWalletService($conn),
                'mypos'      => new MyPosService($conn),
                'eurobank'   => new EurobankService($conn),
                'boc'        => new BankOfCyprusService($conn),
                default      => throw new \Exception("Unsupported provider: {$conn->provider}"),
            };

            $count = $service->syncTransactions($dateFrom, $dateTo);

            // Update last known accounts/balances
            if (method_exists($service, 'getAccounts')) {
                $accounts = $service->getAccounts();
                $credentials = $conn->credentials;
                $credentials['last_known_accounts'] = $accounts;
                $conn->update(['credentials' => $credentials]);
            }

            return back()->with('success', "Synced {$count} transactions from {$conn->label}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Sync failed: ' . $e->getMessage());
        }
    }

    public function refreshBalances($id)
    {
        $conn = BankingConnection::where('workspace_id', $this->workspaceId())->findOrFail($id);

        try {
            $service = match($conn->provider) {
                'vivawallet' => new VivaWalletService($conn),
                'mypos'      => new MyPosService($conn),
                'eurobank'   => new EurobankService($conn),
                'boc'        => new BankOfCyprusService($conn),
                default      => throw new \Exception("Unsupported provider: {$conn->provider}"),
            };

            if (method_exists($service, 'refreshAccounts')) {
                $service->refreshAccounts();
            } else if (method_exists($service, 'getAccounts')) {
                $accounts = $service->getAccounts();
                $credentials = $conn->credentials;
                $credentials['last_known_accounts'] = $accounts;
                $conn->update(['credentials' => $credentials]);
            }

            return back()->with('success', "Balances refreshed for {$conn->label}.");
        } catch (\Exception $e) {
            return back()->with('error', 'Refresh failed: ' . $e->getMessage());
        }
    }

    // --------------------------------------------------------------- LINK
    public function link(Request $request, $id)
    {
        $wsId = $this->workspaceId();
        $tx   = BankTransaction::where('workspace_id', $wsId)->findOrFail($id);

        $validated = $request->validate([
            'linked_type' => 'required|in:invoice,expense',
            'linked_id'   => 'required|integer',
        ]);

        // Verify the linked record belongs to this workspace
        if ($validated['linked_type'] === 'invoice') {
            Invoice::where('workspace_id', $wsId)->findOrFail($validated['linked_id']);
        } else {
            Expense::where('workspace_id', $wsId)->findOrFail($validated['linked_id']);
        }

        $tx->update([
            'linked_type' => $validated['linked_type'],
            'linked_id'   => $validated['linked_id'],
        ]);

        return back()->with('success', 'Transaction linked successfully.');
    }

    // ------------------------------------------------------------- UNLINK
    public function unlink($id)
    {
        $tx = BankTransaction::where('workspace_id', $this->workspaceId())->findOrFail($id);
        $tx->update(['linked_type' => null, 'linked_id' => null]);

        return back()->with('success', 'Link removed.');
    }

    // ------------------------------------------------------------- CONNECT
    public function connect($id)
    {
        $wsId = $this->workspaceId();
        $conn = BankingConnection::where('workspace_id', $wsId)->findOrFail($id);

        try {
            $service = match($conn->provider) {
                'boc'   => new BankOfCyprusService($conn),
                'eurobank' => new EurobankService($conn),
                default => throw new \Exception("Provider {$conn->provider} does not support redirect connection."),
            };

            if (method_exists($service, 'getAuthorizationUrl')) {
                return Inertia::location($service->getAuthorizationUrl());
            }

            return back()->with('error', "No authorization flow for {$conn->provider}");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // ------------------------------------------------------------- CALLBACK
    public function callback(Request $request, $provider)
    {
        $code = $request->get('code');
        $error = $request->get('error');
        $state = $request->get('state');

        if ($error) {
            return redirect()->route('banking.index')->with('error', "Bank authentication failed: {$error}");
        }

        if ($code) {
            try {
                if (! $state) {
                    throw new \Exception('Missing OAuth state.');
                }

                $payload = json_decode(Crypt::decryptString($state), true, flags: JSON_THROW_ON_ERROR);

                if (($payload['provider'] ?? null) !== $provider) {
                    throw new \Exception('OAuth state provider mismatch.');
                }

                $conn = BankingConnection::where('id', $payload['connection_id'] ?? null)
                    ->where('provider', $provider)
                    ->first();

                if (! $conn) {
                    throw new \Exception("No eligible {$provider} connection found to finalize.");
                }

                $user = $request->user();
                $canAccessWorkspace = $user?->isSuperAdmin()
                    || $user?->workspaces()->where('workspaces.id', $conn->workspace_id)->exists();

                if (! $canAccessWorkspace) {
                    throw new \Exception('Unauthorized banking callback.');
                }

                $storedState = $conn->credential('oauth_state');
                $expiresAt = $conn->credential('oauth_state_expires_at');

                if (! $storedState || ! hash_equals($storedState, (string) ($payload['oauth_state'] ?? ''))) {
                    throw new \Exception('OAuth state validation failed.');
                }

                if ($expiresAt && now()->isAfter(\Illuminate\Support\Carbon::parse($expiresAt))) {
                    throw new \Exception('OAuth state has expired.');
                }

                $service = match($provider) {
                    'boc'      => new \App\Services\Banking\BankOfCyprusService($conn),
                    'eurobank' => new \App\Services\Banking\EurobankService($conn),
                    default    => null,
                };

                if ($service && method_exists($service, 'finalizeCallback')) {
                    $service->finalizeCallback($code);
                    if (method_exists($service, 'clearOAuthState')) {
                        $service->clearOAuthState();
                    }
                    return redirect()->route('banking.index')->with('success', "Authenticated with {$provider} successfully!");
                }
            } catch (\Exception $e) {
                \Log::error("{$provider} Callback error: " . $e->getMessage());
                return redirect()->route('banking.index')->with('error', "Token exchange failed: " . $e->getMessage());
            }
        }

        return redirect()->route('banking.index')->with('success', "Authenticated with {$provider} successfully.");
    }
}
