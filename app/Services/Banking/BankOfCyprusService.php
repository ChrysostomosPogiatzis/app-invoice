<?php

namespace App\Services\Banking;

use App\Models\BankingConnection;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BankOfCyprusService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct(private BankingConnection $connection)
    {
        $isDemo = config('services.boc.is_demo', true);

        $this->baseUrl = $isDemo
            ? 'https://sandbox-apis.bankofcyprus.com/df-boc-org-sb/sb/psd2'
            : 'https://api.bankofcyprus.com';

        $this->clientId = config('services.boc.client_id');
        $this->clientSecret = config('services.boc.client_secret');
    }

    /**
     * ----------------------------------------
     * STEP 1: SYSTEM TOKEN
     * ----------------------------------------
     */
    private function getSystemToken(): string
    {
        $response = Http::asForm()->post("{$this->baseUrl}/oauth2/token", [
            'grant_type' => 'client_credentials',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope' => 'TPPOAuth2Security',
        ]);

        if (!$response->successful()) {
            throw new \Exception('BoC system token failed: ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * ----------------------------------------
     * STEP 2: CREATE SUBSCRIPTION (ALWAYS NEW)
     * ----------------------------------------
     */
    private function createSubscription(): string
    {
        $token = $this->getSystemToken();

        $response = Http::withToken($token)
            ->withHeaders($this->baseHeaders())
            ->post("{$this->baseUrl}/v1/subscriptions", [
                'accounts' => [
                    'transactionHistory' => true,
                    'balance' => true,
                    'details' => true,
                    'checkFundsAvailability' => true,
                ],
                'payments' => [
                    'limit' => 999999,
                    'currency' => 'EUR',
                    'amount' => 999999,
                ],
            ]);

        if (!$response->successful()) {
            throw new \Exception('BoC subscription failed: ' . $response->body());
        }

        $subscriptionId = $response->json('subscriptionId');

        // 🔥 Reset tokens (CRITICAL)
        $this->updateCredentials([
            'subscription_id' => $subscriptionId,
            'user_access_token' => null,
            'user_refresh_token' => null,
            'subscription_status' => 'CREATED',
        ]);

        return $subscriptionId;
    }

    /**
     * ----------------------------------------
     * STEP 3: AUTH URL
     * ----------------------------------------
     */
    public function getAuthorizationUrl(): string
    {
        $subscriptionId = $this->createSubscription();

        $redirectUri = route('banking.callback', ['provider' => 'boc']);
        $oauthState = (string) Str::uuid();

        $this->updateCredentials([
            'oauth_state' => $oauthState,
            'oauth_state_expires_at' => now()->addMinutes(15)->toIso8601String(),
        ]);

        $statePayload = Crypt::encryptString(json_encode([
            'provider' => 'boc',
            'connection_id' => $this->connection->id,
            'oauth_state' => $oauthState,
        ]));

        return "{$this->baseUrl}/oauth2/authorize?" . 
            "response_type=code" .
            "&redirect_uri=" . $redirectUri .
            "&scope=UserOAuth2Security" .
            "&client_id=" . $this->clientId .
            "&subscriptionid=" . $subscriptionId .
            "&state=" . urlencode($statePayload);
    }

    /**
     * ----------------------------------------
     * STEP 4: CALLBACK → TOKEN
     * ----------------------------------------
     */
    public function finalizeCallback(string $code): void
    {
        $redirectUri = route('banking.callback', ['provider' => 'boc']);

        $response = Http::asForm()->post("{$this->baseUrl}/oauth2/token", [
            'grant_type' => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'code' => $code,
            'redirect_uri' => $redirectUri,
            'scope' => 'UserOAuth2Security',
        ]);

        if (!$response->successful()) {
            throw new \Exception('BoC token exchange failed: ' . $response->body());
        }

        $this->updateCredentials([
            'user_access_token' => $response->json('access_token'),
            'user_refresh_token' => $response->json('refresh_token'),
        ]);

        // Activate + verify immediately
        $this->activateSubscription();

        // Save accounts and mark connection as active for the UI
        $accounts = $this->getAccounts();
        $this->connection->update([
            'is_active' => true,
            'credentials' => array_merge($this->connection->credentials, [
                'last_known_accounts' => $accounts,
                'subscription_status' => 'ACTV'
            ])
        ]);
    }

    public function clearOAuthState(): void
    {
        $creds = $this->connection->credentials;
        unset($creds['oauth_state'], $creds['oauth_state_expires_at']);
        $this->connection->update(['credentials' => $creds]);
    }

    /**
     * ----------------------------------------
     * STEP 5: ACTIVATE (SIMPLIFIED)
     * ----------------------------------------
     */
    private function activateSubscription(): void
    {
        $token = $this->requireUserToken();
        $subscriptionId = $this->connection->credential('subscription_id');

        $response = Http::withToken($token)
            ->withHeaders($this->authHeaders($subscriptionId))
            ->patch("{$this->baseUrl}/v1/subscriptions/{$subscriptionId}", [
                'status' => 'ACTV'
            ]);

        if (!$response->successful()) {
            throw new \Exception('BoC activation failed: ' . $response->body());
        }

        $this->updateCredentials([
            'subscription_status' => 'ACTV',
        ]);
    }

    /**
     * ----------------------------------------
     * VERIFY ACCESS (CRITICAL DEBUG STEP)
     * ----------------------------------------
     */
    private function verifyAccess(): void
    {
        $response = Http::withToken($this->getSystemToken())
            ->withHeaders($this->authHeaders())
            ->get("{$this->baseUrl}/v1/accounts");

        if (!$response->successful()) {
            throw new \Exception('BoC verification failed: ' . $response->body());
        }
    }

    /**
     * ----------------------------------------
     * GET ACCOUNTS
     * ----------------------------------------
     */
    public function getAccounts(bool $fetchBalances = true): array
    {
        $response = Http::withToken($this->getSystemToken())
            ->withHeaders($this->authHeaders())
            ->get("{$this->baseUrl}/v1/accounts");

        $raw = $response->json() ?? [];
        
        // If we hit a rate limit error, try to use the stored accounts from the DB
        if (isset($raw['error']) || !$response->successful()) {
             $stored = $this->connection->credential('last_known_accounts');
             if ($stored) return $stored;
             throw new \Exception('BoC Accounts failed and no cache available: ' . json_encode($raw));
        }

        $accounts = [];
        foreach ($raw as $acc) {
            $accountId = $acc['accountId'] ?? null;
            if (!$accountId) continue;

            $detail = $acc;
            if ($fetchBalances) {
                // Fetch specific account detail to get the balance
                $detailResponse = Http::withToken($this->getSystemToken())
                    ->withHeaders($this->authHeaders())
                    ->get("{$this->baseUrl}/v1/accounts/{$accountId}");

                $detail = $detailResponse->json() ?? $acc;
                if (is_array($detail) && isset($detail[0])) {
                    $detail = $detail[0];
                }
            }

            Log::info('BoC account detail fetched', [
                'connection_id' => $this->connection->id,
                'account_ref' => $this->maskAccountReference($accountId),
                'currency' => $detail['currency'] ?? 'EUR',
            ]);

            $accounts[] = [
                'name'           => $detail['accountAlias'] ?? $detail['accountName'] ?? 'Account',
                'iban'           => $detail['IBAN'] ?? $detail['accountId'] ?? 'Unknown',
                'account_number' => $detail['accountId'] ?? '',
                'currency'       => $detail['currency'] ?? 'EUR',
                'balance'        => $detail['balances'][0]['amount'] 
                                    ?? $detail['balances'][0]['balanceAmount']['amount'] 
                                    ?? 0,
                'raw_id'         => $accountId,
            ];
        }

        return $accounts;
    }

    /**
     * Refresh accounts and update the database record.
     */
    public function refreshAccounts(): array
    {
        $accounts = $this->getAccounts();
        $this->updateCredentials(['last_known_accounts' => $accounts]);
        return $accounts;
    }

    /**
     * ----------------------------------------
     * SYNC TRANSACTIONS
     * ----------------------------------------
     */
    public function syncTransactions(string $from, string $to): int
    {
        // Use cached accounts if possible to save API calls
        $accounts = $this->connection->credential('last_known_accounts') ?: $this->getAccounts(false);

        if (empty($accounts)) {
            throw new \Exception('No accounts found.');
        }

        // Use the normalized keys
        $accountId = $accounts[0]['account_number'] ?? $accounts[0]['raw_id'] ?? $accounts[0]['iban'];

        $queryParams = [
            'startDate' => date('d/m/Y', strtotime($from)),
            'endDate' => date('d/m/Y', strtotime($to)),
            'maxCount' => 0,
        ];

        Log::info('BoC transaction sync started', [
            'connection_id' => $this->connection->id,
            'account_ref' => $this->maskAccountReference($accountId),
            'start_date' => $queryParams['startDate'],
            'end_date' => $queryParams['endDate'],
        ]);

        $response = Http::withToken($this->getSystemToken())
            ->withHeaders($this->authHeaders())
            ->get("{$this->baseUrl}/v1/accounts/{$accountId}/statement", $queryParams);

        if (!$response->successful()) {
            Log::error('BoC transaction sync failed', [
                'connection_id' => $this->connection->id,
                'account_ref' => $this->maskAccountReference($accountId),
                'status' => $response->status(),
            ]);
            throw new \Exception('BoC transactions failed: ' . $response->body());
        }

        $rawBody = $response->json();
        $transactions = $rawBody['transaction'] ?? [];
        Log::info('BoC transaction sync response received', [
            'connection_id' => $this->connection->id,
            'account_ref' => $this->maskAccountReference($accountId),
            'transaction_count' => count($transactions),
        ]);

        $count = 0;

        foreach ($transactions as $tx) {
            $date = \Illuminate\Support\Carbon::createFromFormat('d/m/Y', $tx['postingDate'] ?? $tx['valueDate'])->startOfDay();
            
            $amount = $tx['transactionAmount']['amount'] ?? 0;
            if (($tx['dcInd'] ?? '') === 'DEBIT') {
                $amount = -$amount;
            }

            \App\Models\BankTransaction::updateOrCreate(
                [
                    'banking_connection_id' => $this->connection->id,
                    'external_id' => $tx['id']
                ],
                [
                    'workspace_id' => $this->connection->workspace_id,
                    'provider' => 'boc',
                    'transaction_date' => $date->toDateTimeString(),
                    'amount' => $amount,
                    'currency' => $tx['transactionAmount']['currency'] ?? 'EUR',
                    'type' => $tx['transactionType'] ?? null,
                    'reference' => $tx['description'] ?? $tx['id'],
                    'description' => $tx['description'] ?? null,
                    'raw_payload' => $tx,
                ]
            );
            $count++;
        }

        return $count;
    }

    private function maskAccountReference(?string $reference): string
    {
        if (! $reference) {
            return 'unknown';
        }

        $suffix = substr($reference, -4);

        return str_repeat('*', max(strlen($reference) - 4, 0)) . $suffix;
    }

    /**
     * ----------------------------------------
     * HELPERS
     * ----------------------------------------
     */
    private function requireUserToken(): string
    {
        $token = $this->connection->credential('user_access_token');

        if (!$token) {
            throw new \Exception('BoC: User not authorized.');
        }

        return $token;
    }

    private function updateCredentials(array $data): void
    {
        $creds = array_merge($this->connection->credentials ?? [], $data);
        $this->connection->update(['credentials' => $creds]);
    }

    private function baseHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'timeStamp' => (string) time(),
            'journeyId' => (string) Str::uuid(),
        ];
    }

    private function authHeaders(?string $subscriptionId = null): array
    {
        return [
            'accept' => 'application/json',
            'Content-Type' => 'application/json',
            'client_id' => $this->clientId,
            'subscriptionId' => $subscriptionId ?? $this->connection->credential('subscription_id'),
            'journeyId' => (string) Str::uuid(),
            'timeStamp' => (string) time(),
            'X-Request-ID' => (string) Str::uuid(),

            // 🔥 SANDBOX SAFE VALUES
            'PSU-ID' => 'demo',
            'customerIP' => '127.0.0.1',
        ];
    }
}
