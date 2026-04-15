<?php

namespace App\Services\Banking;

use App\Models\BankingConnection;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Eurobank Cyprus PSD2 Integration
 * 
 * Based on Berlin Group NextGenPSD2 standard and specific Eurobank documentation.
 */
class EurobankService
{
    private string $baseUrl;
    private string $tppTokenUrl;
    private string $userAuthUrl;
    private string $userTokenUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct(private BankingConnection $connection)
    {
        $isDemo = $connection->credential('is_demo', false);
        
        if ($isDemo) {
            $this->baseUrl      = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/sandbox/v1';
            $this->tppTokenUrl  = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/tppdev/oauth2/token';
            $this->userAuthUrl  = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/oauth-t24-test/oauth2/authorize';
            $this->userTokenUrl = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/oauth-t24-test/oauth2/token';
        } else {
            $this->baseUrl      = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/prod/v1';
            $this->tppTokenUrl  = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/oauth-infinity/oauth2/token';
            $this->userAuthUrl  = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/oauth-infinity/oauth2/authorize';
            $this->userTokenUrl = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/oauth-infinity/oauth2/token';
        }

        $this->clientId     = config('services.eurobank.client_id');
        $this->clientSecret = config('services.eurobank.client_secret');
    }

    /**
     * Eurobank Requirement Step 1: Obtain TPP Access Token (client_credentials)
     */
    private function getTppToken(): string
    {
        $response = Http::asForm()->post($this->tppTokenUrl, [
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => 'account.setup',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Eurobank TPP Auth failed: ' . $response->body());
        }

        return $response->json('access_token');
    }

    /**
     * Eurobank Requirement Step 2 & 3: Create Consent and Build Authorize URL
     */
    public function getAuthorizationUrl(): string
    {
        $tppToken = $this->getTppToken();
        $requestId = (string) Str::uuid();
        $redirectUri = route('banking.callback', ['provider' => 'eurobank']);

        // POST /consents
        $response = Http::withToken($tppToken)
            ->withHeaders([
                'X-IBM-Client-Id' => $this->clientId,
                'X-Request-ID'    => $requestId,
                'TPP-Redirect-URI' => $redirectUri,
                'Accept'          => 'application/json',
                'Content-Type'    => 'application/json',
            ])->post("{$this->baseUrl}/consents", [
                'access' => [
                    'allPsd2' => 'allAccounts',
                ],
                'recurringIndicator' => true,
                'validUntil' => now()->addDays(90)->format('Y-m-d'),
                'frequencyPerDay' => 4,
                'combinedServiceIndicator' => false,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Eurobank Consent creation failed: ' . $response->body());
        }

        $consentId = $response->json('consentId');
        
        // Store consentId for later exchange
        $creds = $this->connection->credentials;
        $creds['consent_id'] = $consentId;
        $this->connection->update(['credentials' => $creds]);

        // If scaRedirect link is provided and is a real URL, use it. 
        // Note: Eurobank Sandbox sometimes returns literal "redirectLink"
        $scaLink = $response->json('_links.scaRedirect.href');
        if ($scaLink && filter_var($scaLink, FILTER_VALIDATE_URL)) {
            return $scaLink;
        }

        return $this->userAuthUrl . '?' . http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'scope'         => 'AISP',
            'redirect_uri'  => $redirectUri,
            'consent'       => $consentId,
            'state'         => $this->connection->id,
        ]);
    }

    /**
     * Step 4: Exchange Authorization Code for User Access Token
     */
    public function finalizeCallback(string $code): void
    {
        $redirectUri = route('banking.callback', ['provider' => 'eurobank']);
        $consentId = $this->connection->credential('consent_id');

        $response = Http::asForm()
            ->withHeaders([
                'consent' => $consentId,
            ])
            ->post($this->userTokenUrl, [
                'grant_type'    => 'authorization_code',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'code'          => $code,
                'redirect_uri'  => route('banking.callback', ['provider' => 'eurobank']),
                'scope'         => 'AISP',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Eurobank code exchange failed: ' . $response->body());
        }

        $data = $response->json();
        
        $creds = $this->connection->credentials;
        $creds['access_token']     = $data['access_token'] ?? null;
        $creds['refresh_token']    = $data['refresh_token'] ?? null;
        $creds['token_expires_at'] = now()->addSeconds($data['expires_in'] ?? 1800)->toDateTimeString();
        $creds['psu_ip']           = request()->ip(); // Store PSU IP for background jobs
        
        $this->connection->update([
            'credentials' => $creds,
            'is_active'   => true,
        ]);
    }

    /**
     * Handle Token Refresh
     */
    private function ensureValidToken(): string
    {
        $token     = $this->connection->credential('access_token');
        $expiresAt = $this->connection->credential('token_expires_at');

        if ($token && $expiresAt && now()->addSeconds(60)->lessThan($expiresAt)) {
            return $token;
        }

        $refreshToken = $this->connection->credential('refresh_token');
        $consentId    = $this->connection->credential('consent_id');
        
        if (!$refreshToken) {
            throw new \Exception('Eurobank session expired. Please re-connect.');
        }

        $response = Http::asForm()
            ->withHeaders(['consent' => $consentId])
            ->post($this->userTokenUrl, [
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
                'redirect_uri'  => route('banking.callback', ['provider' => 'eurobank']),
            ]);

        if (!$response->successful()) {
            throw new \Exception('Eurobank token refresh failed: ' . $response->body());
        }

        $data = $response->json();
        $creds = $this->connection->credentials;
        $creds['access_token']     = $data['access_token'];
        $creds['refresh_token']    = $data['refresh_token'] ?? $refreshToken;
        $creds['token_expires_at'] = now()->addSeconds($data['expires_in'] ?? 1800)->toDateTimeString();
        
        $this->connection->update(['credentials' => $creds]);

        return $data['access_token'];
    }

    /**
     * Sync Transactions for all associated accounts
     */
    public function syncTransactions(?string $dateFrom = null, ?string $dateTo = null): int
    {
        $token     = $this->ensureValidToken();
        $consentId = $this->connection->credential('consent_id');
        
        $dateFrom = $dateFrom ?: now()->subDays(60000)->format('Y-m-d');
        $dateTo   = $dateTo   ?: now()->format('Y-m-d');

        $accounts = $this->getAccounts();
        if (empty($accounts)) {
            return 0;
        }

        $count = 0;
        foreach ($accounts as $acc) {
            $resourceId = $acc['resourceId'] ?? null;
            if (!$resourceId) continue;

            $nextUrl = "{$this->baseUrl}/accounts/{$resourceId}/transactions";
            $queryParams = [
                'dateFrom'      => $dateFrom,
                'dateTo'        => $dateTo,
                'bookingStatus' => 'booked',
            ];

            while ($nextUrl) {
                $requestId = (string) Str::uuid();
                $response = Http::withToken($token)
                    ->withHeaders([
                        'X-Request-ID'     => $requestId,
                        'X-IBM-Client-Id'  => $this->clientId,
                        'Consent-ID'       => $consentId,
                        'TPP-Redirect-URI' => route('banking.callback', ['provider' => 'eurobank']),
                        'PSU-IP-Address'   => $this->connection->credential('psu_ip') ?? '127.0.0.1',
                        'Accept'           => 'application/json',
                    ])
                    ->get($nextUrl, $queryParams);

                // Clear query params after first call if nextUrl is a full URL from _links
                if ($nextUrl !== "{$this->baseUrl}/accounts/{$resourceId}/transactions") {
                    $queryParams = []; 
                }

                if (!$response->successful()) {
                    \Log::error("Eurobank transactions sync failed for account {$resourceId}: " . $response->body());
                    break;
                }

                $data = $response->json();
                $transactions = $data['transactions']['booked'] ?? [];

                foreach ($transactions as $tx) {
                    $externalId = $tx['transactionId'] ?? $tx['entryReference'] ?? null;
                    if (!$externalId) continue;

                    $amount = (float)($tx['transactionAmount']['amount'] ?? 0);
                    // Critical Fix: Amount Sign Handling
                    if (($tx['creditDebitIndicator'] ?? null) === 'DBIT') {
                        $amount *= -1;
                    }
    echo "Tx ID: {$externalId}, Amount: {$amount}, Date: {$tx['bookingDate']}\n";

                    BankTransaction::updateOrCreate(
                        ['banking_connection_id' => $this->connection->id, 'external_id' => $externalId],
                        [
                            'workspace_id'     => $this->connection->workspace_id,
                            'provider'         => 'eurobank',
                            'transaction_date' => $tx['bookingDate'] ?? now(),
                            'type'             => $tx['proprietaryBankTransactionCode'] ?? null,
                            'amount'           => $amount,
                            'currency'         => $tx['transactionAmount']['currency'] ?? 'EUR',
                            'status'           => 'booked',
                            'reference'        => $tx['remittanceInformationUnstructured'] ?? null,
                            'description'      => $tx['debtorName'] ?? $tx['creditorName'] ?? null,
                            'raw_payload'      => $tx,
                        ]
                    );
                    $count++;
                }

                // Pagination Handling
                $nextUrl = $data['_links']['next']['href'] ?? null;
                if ($nextUrl && !str_starts_with($nextUrl, 'http')) {
                    $nextUrl = "https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/prod" . $nextUrl;
                }
            }
        }

        $this->connection->update(['last_synced_at' => now()]);
        return $count;
    }

    /**
     * Fetch all associated accounts
     */
    public function getAccounts(): array
    {
        $token     = $this->ensureValidToken();
        $consentId = $this->connection->credential('consent_id');
        $requestId = (string) Str::uuid();

        $response = Http::withToken($token)
            ->withHeaders([
                'X-Request-ID'     => $requestId,
                'X-IBM-Client-Id'  => $this->clientId,
                'Consent-ID'       => $consentId,
                'TPP-Redirect-URI' => route('banking.callback', ['provider' => 'eurobank']),
                'PSU-IP-Address'   => '127.0.0.1',
                'Accept'           => 'application/json',
            ])
            ->get("{$this->baseUrl}/accounts", [
                'withBalance' => 'true'
            ]);

        if (!$response->successful()) {
            \Log::error('Eurobank accounts fetch failed: ' . $response->body());
            return [];
        }

        $accounts = $response->json('accounts') ?? [];
        
        $enrichedAccounts = [];
        foreach ($accounts as $a) {
            // Pick balance from the inline balances array if present
            $balance = 0;
            if (!empty($a['balances'])) {
                // Usually the first element contains the most relevant balance
                $balance = $a['balances'][0]['balanceAmount']['amount'] ?? 0;
            }

            $enrichedAccounts[] = [
                'name'       => $a['name'] ?? $a['product'] ?? 'Eurobank Account',
                'iban'       => $a['iban'] ?? '',
                'currency'   => $a['currency'] ?? 'EUR',
                'resourceId' => $a['resourceId'] ?? null,
                'balance'    => (float)$balance,
            ];
        }

        $creds = $this->connection->credentials;
        $creds['last_known_accounts'] = $enrichedAccounts;
        $this->connection->update(['credentials' => $creds]);

        return $enrichedAccounts;
    }
}
