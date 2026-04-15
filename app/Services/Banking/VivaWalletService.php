<?php

namespace App\Services\Banking;

use App\Models\BankingConnection;
use App\Models\BankTransaction;
use Illuminate\Support\Facades\Http;

class VivaWalletService
{
    private string $baseUrl;
    private string $tokenUrl;

    public function __construct(private BankingConnection $connection)
    {
        $isDemo = $connection->credential('is_demo', false);

        $this->tokenUrl = $isDemo
            ? 'https://demo-accounts.vivapayments.com/connect/token'
            : 'https://accounts.vivapayments.com/connect/token';

        $this->baseUrl = $isDemo
            ? 'https://demo-api.vivapayments.com'
            : 'https://api.vivapayments.com';
    }

    private function getAccessToken(): string
    {
        $response = Http::withBasicAuth(
            $this->connection->credential('client_id'),
            $this->connection->credential('client_secret')
        )->asForm()->post($this->tokenUrl, [
            'grant_type' => 'client_credentials',
        ]);

        if (! $response->successful()) {
            throw new \Exception('Viva Wallet auth failed: ' . $response->body());
        }

        return $response->json('access_token');
    }

    public function syncTransactions(string $dateFrom, string $dateTo): int
    {
        $token = $this->getAccessToken();

        $response = Http::withToken($token)
            ->get("{$this->baseUrl}/checkout/v2/transactions", [
                'dateFrom' => $dateFrom,
                'dateTo'   => $dateTo,
            ]);

        if (! $response->successful()) {
            throw new \Exception('Viva Wallet fetch failed: ' . $response->body());
        }

        $rows  = $response->json('Transactions') ?? $response->json('transactions') ?? [];
        $count = 0;

        foreach ($rows as $tx) {
            $externalId = $tx['TransactionId'] ?? $tx['transactionId'] ?? null;
            if (! $externalId) continue;

            BankTransaction::updateOrCreate(
                ['banking_connection_id' => $this->connection->id, 'external_id' => $externalId],
                [
                    'workspace_id'     => $this->connection->workspace_id,
                    'provider'         => 'vivawallet',
                    'transaction_date' => $tx['Created'] ?? now(),
                    'type'             => $tx['TransactionTypeId'] ?? null,
                    'amount'           => ($tx['Amount'] ?? 0) / 100,
                    'currency'         => $tx['CurrencyCode'] ?? 'EUR',
                    'status'           => $tx['StatusId'] ?? null,
                    'card_type'        => $tx['CardTypeId'] ?? null,
                    'card_last4'       => $tx['CardNumber'] ?? null,
                    'reference'        => $tx['OrderCode'] ?? null,
                    'description'      => $tx['CustomerDescription'] ?? null,
                    'raw_payload'      => $tx,
                ]
            );
            $count++;
        }

        $this->connection->update(['last_synced_at' => now()]);
        return $count;
    }
}
