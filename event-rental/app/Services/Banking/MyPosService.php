<?php

namespace App\Services\Banking;

use App\Models\BankingConnection;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\Http;

/**
 * MyPOS Banking API Integration
 *
 * Strictly following the 'Dual-authentication mechanism' from developers.mypos.com/apis/identity
 */
class MyPosService
{
    private string $baseUrl;
    private bool $isPartnerFlow = false;

    public function __construct(private BankingConnection $connection)
    {
        $isDemo = $connection->credential('is_demo', false);
        $clientId = $connection->credential('client_id', '');

        // Detect if it's the Partner Flow (starts with client_*) or Merchant Flow (alphanumeric)
        $this->isPartnerFlow = str_starts_with($clientId, 'client_');

        if ($this->isPartnerFlow) {
            $this->baseUrl = $isDemo
                ? 'https://demo-api-gateway.mypos.com'
                : 'https://api-gateway.mypos.com';
        } else {
            // Direct Merchant REST API
            $this->baseUrl = $isDemo
                ? 'https://demo.api.mypos.com'
                : 'https://api.mypos.com';
        }
    }

    /**
     * Step 1: Generate OAuth2 Bearer Token
     */
    private function getBearerToken(): string
    {
        $clientId = $this->connection->credential('client_id');
        $clientSecret = $this->connection->credential('client_secret');

        if (!$clientId || !$clientSecret) {
            throw new \Exception('MyPOS: client_id or client_secret is missing.');
        }

        $path = $this->isPartnerFlow ? '/api/v1/oauth/token' : '/v1/oauth2/token';

        $response = Http::asForm()
            ->withHeaders(['Accept' => 'application/json'])
            ->post("{$this->baseUrl}{$path}", [
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);

        // Fallback for Merchant flow 404
        if (!$this->isPartnerFlow && $response->status() === 404) {
            $response = Http::asForm()
                ->withHeaders(['Accept' => 'application/json'])
                ->post("{$this->baseUrl}/v1/oauth/token", [
                    'grant_type' => 'client_credentials',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                ]);
        }

        if (!$response->successful()) {
            throw new \Exception(
                'MyPOS bearer token failed: HTTP ' . $response->status() . ' — ' . $response->body()
            );
        }

        $token = $response->json('access_token');
        if (!$token) {
            throw new \Exception('MyPOS bearer token missing in response: ' . $response->body());
        }

        return $token;
    }

    /**
     * Step 2: Create Session ID
     */
    private function getSessionId(string $bearerToken): string
    {
        if (!$this->isPartnerFlow) {
            throw new \Exception('Session is only valid for Partner flow.');
        }

        $merchantClientId = $this->connection->credential('merchant_client_id');
        $merchantClientSecret = $this->connection->credential('merchant_client_secret');
        $partnerId = $this->connection->credential('partner_id');
        $appId = $this->connection->credential('application_id');

        if (!$merchantClientId || !$merchantClientSecret) {
            throw new \Exception('Missing merchant credentials.');
        }

        if (!$partnerId || !$appId) {
            throw new \Exception('Missing partner_id or application_id.');
        }

        $response = Http::withToken($bearerToken)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Partner-Id' => $partnerId,
                'X-Application-Id' => $appId,
            ])
            ->post("{$this->baseUrl}/api/v1/auth/session", [
                'client_id'     => $merchantClientId,
                'client_secret' => $merchantClientSecret,
            ]);

        if (!$response->successful()) {
            throw new \Exception(
                'MyPOS session creation failed: HTTP ' .
                $response->status() . ' — ' . $response->body()
            );
        }

        return $response->json('session_id')
            ?? $response->json('session')
            ?? throw new \Exception('Session ID missing in response.');
    }
    /**
     * Step 3: Make Authenticated API Requests
     */
    public function syncTransactions(string $dateFrom, string $dateTo): int
    {
        $bearerToken = $this->getBearerToken();
        $sessionId = $this->getSessionId($bearerToken);

        $headers = [
            'Authorization' => 'Bearer ' . $bearerToken,
            'Accept' => 'application/json',
        ];

        if ($this->isPartnerFlow) {
            if ($sessionId)
                $headers['X-Session'] = $sessionId;

            $partnerId = $this->connection->credential('partner_id');
            $appId = $this->connection->credential('application_id');

            if ($partnerId)
                $headers['X-Partner-Id'] = $partnerId;
            if ($appId)
                $headers['X-Application-Id'] = $appId;

            // Added x-api-version as seen in documentation Step 3 example
            $headers['Content-Type'] = 'application/json; x-api-version=1';
        }

        $path = $this->isPartnerFlow ? '/accounting/v1/transactions' : '/v1/reporting/accounts/transactions';

        $response = Http::withHeaders($headers)
            ->get("{$this->baseUrl}{$path}", [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'pageSize' => 500,
            ]);

        // Fallback for direct merchant reporting
        if (!$this->isPartnerFlow && $response->status() === 404) {
            $response = Http::withHeaders($headers)
                ->get("{$this->baseUrl}/v1/transactions", [
                    'dateFrom' => $dateFrom,
                    'dateTo' => $dateTo,
                ]);
        }

        if (!$response->successful()) {
            throw new \Exception(
                'MyPOS transactions fetch failed: HTTP ' . $response->status() . ' — ' . $response->body()
            );
        }

        $rows = $response->json('data') ?? $response->json('transactions') ?? $response->json('list') ?? [];
        $count = 0;

        foreach ($rows as $tx) {
            $externalId = $tx['id'] ?? $tx['transactionId'] ?? $tx['reference'] ?? null;
            if (!$externalId)
                continue;

            BankTransaction::updateOrCreate(
                ['banking_connection_id' => $this->connection->id, 'external_id' => $externalId],
                [
                    'workspace_id' => $this->connection->workspace_id,
                    'provider' => 'mypos',
                    'transaction_date' => $tx['transactionDate'] ?? $tx['created_at'] ?? $tx['date'] ?? now(),
                    'type' => $tx['transactionType'] ?? $tx['type'] ?? null,
                    'amount' => $tx['amount'] ?? 0,
                    'currency' => $tx['currency'] ?? 'EUR',
                    'status' => $tx['status'] ?? null,
                    'card_type' => $tx['cardBrand'] ?? $tx['card_type'] ?? null,
                    'card_last4' => $tx['cardLastFour'] ?? $tx['card_last4'] ?? null,
                    'reference' => $tx['orderReference'] ?? $tx['reference'] ?? null,
                    'description' => $tx['description'] ?? null,
                    'raw_payload' => $tx,
                ]
            );
            $count++;
        }

        $this->connection->update(['last_synced_at' => now()]);
        return $count;
    }
}
