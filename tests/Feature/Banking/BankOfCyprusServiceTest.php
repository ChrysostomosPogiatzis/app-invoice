<?php

namespace Tests\Feature\Banking;

use App\Models\BankingConnection;
use App\Models\BankTransaction;
use App\Models\Workspace;
use App\Services\Banking\BankOfCyprusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class BankOfCyprusServiceTest extends TestCase
{
    use RefreshDatabase;

    private Workspace $workspace;
    private BankingConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->workspace = Workspace::create([
            'company_name' => 'Test Workspace',
            'currency' => 'EUR',
        ]);

        $this->connection = BankingConnection::create([
            'workspace_id' => $this->workspace->id,
            'provider' => 'boc',
            'label' => 'BoC Test',
            'is_active' => true,
            'credentials' => [
                'client_id' => 'test-id',
                'client_secret' => 'test-secret',
                'is_demo' => true,
                'subscription_id' => 'sub-123',
                'user_access_token' => 'fake-user-token',
                'account_id' => 'iban-123',
            ],
        ]);
    }

    /** @test */
    public function it_can_sync_transactions_from_boc()
    {
        Http::fake([
            '*/oauth2/token' => Http::response([
                'access_token' => 'fake-sys-token'
            ], 200),
            '*/v1/accounts' => Http::response([
                [
                    'accountId' => 'iban-123',
                    'IBAN' => 'CY1234567890',
                    'currency' => 'EUR'
                ]
            ], 200),
            'https://sandbox-apis.bankofcyprus.com/df-boc-org-sb/sb/psd2/v1/accounts/iban-123/statement*' => Http::response([
                'transaction' => [
                    [
                        'id' => 'BOC-TX-001',
                        'postingDate' => '01/04/2026',
                        'transactionType' => 'Normal',
                        'transactionAmount' => [
                            'amount' => 150.75,
                            'currency' => 'EUR'
                        ],
                        'description' => 'Test Transaction 1'
                    ],
                    [
                        'id' => 'BOC-TX-002',
                        'postingDate' => '02/04/2026',
                        'transactionType' => 'Normal',
                        'transactionAmount' => [
                            'amount' => -25.00,
                            'currency' => 'EUR'
                        ],
                        'description' => 'Test Transaction 2'
                    ]
                ]
            ], 200),
        ]);

        $service = new BankOfCyprusService($this->connection);
        $count = $service->syncTransactions('2026-04-01', '2026-04-07');

        $this->assertEquals(2, $count);
        $this->assertDatabaseHas('bank_transactions', [
            'external_id' => 'BOC-TX-001',
            'amount' => 150.75,
            'provider' => 'boc',
        ]);
        $this->assertDatabaseHas('bank_transactions', [
            'external_id' => 'BOC-TX-002',
            'amount' => -25.00,
            'provider' => 'boc',
        ]);
    }

    /** @test */
    public function it_can_fetch_accounts()
    {
        Http::fake([
            '*/oauth2/token' => Http::response([
                'access_token' => 'fake-sys-token'
            ], 200),
            '*/v1/accounts' => Http::response([
                [
                    'accountId' => 'acc-001',
                    'IBAN' => 'CY1234567890',
                    'currency' => 'EUR',
                    'accountType' => 'Current'
                ]
            ], 200),
            '*/v1/accounts/*' => Http::response([
                [
                    'accountId' => 'acc-001',
                    'balances' => [
                         ['amount' => 0]
                    ]
                ]
            ], 200),
        ]);

        $service = new BankOfCyprusService($this->connection);
        $accounts = $service->getAccounts();

        $this->assertCount(1, $accounts);
        $this->assertEquals('acc-001', $accounts[0]['account_number']);
    }

    /** @test */
    public function it_can_finalize_callback_and_activate_subscription()
    {
        $subscriptionId = 'sub-123';
        $userToken = 'new-user-token';

        Http::fake([
            '*/oauth2/token' => Http::response([
                'access_token' => $userToken,
                'refresh_token' => 'refresh-123'
            ], 200),
            '*/v1/accounts' => Http::response([
                [
                    'accountId' => 'acc-001',
                    'IBAN' => 'CY1234567890',
                    'currency' => 'EUR',
                    'accountType' => 'Current'
                ]
            ], 200),
            '*/v1/accounts/*' => Http::response([
                [
                    'accountId' => 'acc-001',
                    'balances' => [
                         ['amount' => 0]
                    ]
                ]
            ], 200),
            'https://sandbox-apis.bankofcyprus.com/df-boc-org-sb/sb/psd2/v1/subscriptions/' . $subscriptionId => Http::sequence()
                ->push([
                    [
                        'subscriptionId' => $subscriptionId,
                        'status' => 'PENDING'
                    ]
                ], 200) // GET details
                ->push([], 200), // PATCH activate
        ]);

        $service = new BankOfCyprusService($this->connection);
        $service->finalizeCallback('test-code');

        $this->connection->refresh();
        $this->assertEquals($userToken, $this->connection->credential('user_access_token'));
        
        Http::assertSent(function ($request) use ($subscriptionId) {
            return $request->method() === 'PATCH' && 
                   str_contains($request->url(), "/subscriptions/{$subscriptionId}") &&
                   $request['status'] === 'ACTV' &&
                   $request->hasHeader('PSU-ID') &&
                   $request->hasHeader('X-Request-ID');
        });
    }
}
