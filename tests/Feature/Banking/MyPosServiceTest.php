<?php

namespace Tests\Feature\Banking;

use App\Models\BankingConnection;
use App\Models\BankTransaction;
use App\Models\Workspace;
use App\Services\Banking\MyPosService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MyPosServiceTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = Workspace::create([
            'company_name' => 'Test Workspace',
            'currency' => 'EUR',
        ]);
    }

    /** @test */
    public function it_can_sync_transactions_in_merchant_flow()
    {
        \Illuminate\Support\Facades\Config::set('services.mypos.partner_client_id', 'mps-test-123');
        \Illuminate\Support\Facades\Config::set('services.mypos.partner_client_secret', 'secret123');

        $connection = BankingConnection::create([
            'workspace_id' => $this->workspace->id,
            'provider' => 'mypos',
            'label' => 'MyPOS Store Terminal',
            'is_active' => true,
            'credentials' => [
                'merchant_client_id' => 'mps-test-123', // Merchant flow (no client_ prefix)
                'merchant_client_secret' => 'secret123',
                'is_demo' => true,
            ],
        ]);

        Http::fake([
            '*/v1/oauth2/token' => Http::response([
                'access_token' => 'fake-bearer-token',
            ], 200),
            '*/v1/oauth/token' => Http::response([
                'access_token' => 'fake-bearer-token',
            ], 200),
            '*/v1/reporting/accounts/transactions*' => Http::response([
                'data' => [
                    [
                        'id' => 'TXN-001',
                        'transactionDate' => '2026-04-01 10:00:00',
                        'transactionType' => 'purchase',
                        'amount' => 100.50,
                        'currency' => 'EUR',
                        'status' => 'completed',
                        'cardBrand' => 'VISA',
                        'cardLastFour' => '1234',
                        'description' => 'Test Purchase 1',
                    ],
                    [
                        'id' => 'TXN-002',
                        'transactionDate' => '2026-04-02 11:30:00',
                        'transactionType' => 'purchase',
                        'amount' => 50.00,
                        'currency' => 'EUR',
                        'status' => 'completed',
                        'cardBrand' => 'MASTERCARD',
                        'cardLastFour' => '5678',
                        'description' => 'Test Purchase 2',
                    ],
                ],
            ], 200),
            '*/v1/transactions*' => Http::response([
                'data' => [
                    [
                        'id' => 'TXN-001',
                        'transactionDate' => '2026-04-01 10:00:00',
                        'transactionType' => 'purchase',
                        'amount' => 100.50,
                        'currency' => 'EUR'
                    ],
                    [
                        'id' => 'TXN-002',
                        'transactionDate' => '2026-04-02 11:30:00',
                        'transactionType' => 'purchase',
                        'amount' => 50.00,
                        'currency' => 'EUR'
                    ]
                ]
            ], 200)
        ]);

        $service = new MyPosService($connection);
        $count = $service->syncTransactions('2026-04-01', '2026-04-07');

        $this->assertEquals(2, $count);
        $this->assertDatabaseHas('bank_transactions', [
            'external_id' => 'TXN-001',
            'amount' => 100.50,
            'provider' => 'mypos',
        ]);
        $this->assertDatabaseHas('bank_transactions', [
            'external_id' => 'TXN-002',
            'amount' => 50.00,
            'provider' => 'mypos',
        ]);
    }

    /** @test */
    public function it_can_sync_transactions_in_partner_flow()
    {
        \Illuminate\Support\Facades\Config::set('services.mypos.partner_client_id', 'client_partner_123');
        \Illuminate\Support\Facades\Config::set('services.mypos.partner_client_secret', 'partner-secret');
        \Illuminate\Support\Facades\Config::set('services.mypos.partner_id', 'p-123');
        \Illuminate\Support\Facades\Config::set('services.mypos.application_id', 'app-123');

        $connection = BankingConnection::create([
            'workspace_id' => $this->workspace->id,
            'provider' => 'mypos',
            'label' => 'MyPOS Partner Terminal',
            'is_active' => true,
            'credentials' => [
                'merchant_client_id' => 'merch-123',
                'merchant_client_secret' => 'merch-secret',
                'is_demo' => true,
            ],
        ]);

        Http::fake([
            'demo-api-gateway.mypos.com/api/v1/oauth/token' => Http::response([
                'access_token' => 'partner-bearer-token',
            ], 200),
            'demo-api-gateway.mypos.com/api/v1/auth/session' => Http::response([
                'session_id' => 'fake-session-id',
            ], 200),
            'demo-api-gateway.mypos.com/accounting/v1/transactions*' => Http::response([
                'transactions' => [
                    [
                        'transactionId' => 'PTXN-999',
                        'created_at' => '2026-04-05 15:00:00',
                        'type' => 'sale',
                        'amount' => 250.00,
                        'currency' => 'EUR',
                        'status' => 'approved',
                        'card_type' => 'AMEX',
                        'card_last4' => '9999',
                        'orderReference' => 'ORD-123',
                    ],
                ],
            ], 200),
        ]);

        $service = new MyPosService($connection);
        $count = $service->syncTransactions('2026-04-01', '2026-04-07');

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('bank_transactions', [
            'external_id' => 'PTXN-999',
            'amount' => 250.00,
            'reference' => 'ORD-123',
            'provider' => 'mypos',
        ]);
    }

    /** @test */
    public function it_handles_auth_failure()
    {
        $connection = BankingConnection::create([
            'workspace_id' => $this->workspace->id,
            'provider' => 'mypos',
            'label' => 'MyPOS Failure Test',
            'credentials' => [
                'client_id' => 'mps-wrong',
                'client_secret' => 'wrong',
            ],
        ]);

        Http::fake([
            '*' => Http::response(['error' => 'unauthorized'], 401),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('MyPOS bearer token failed');

        $service = new MyPosService($connection);
        $service->syncTransactions('2026-04-01', '2026-04-07');
    }
}
