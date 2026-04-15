<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SyncBankingTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'banking:sync';
    protected $description = 'Synchronize all active banking connections';

    public function handle()
    {
        $connections = \App\Models\BankingConnection::where('is_active', true)->get();
        
        foreach ($connections as $conn) {
            $this->info("--- 🔄 Syncing: {$conn->label} [{$conn->provider}] ---");
            
            try {
                $service = match($conn->provider) {
                    'boc'        => new \App\Services\Banking\BankOfCyprusService($conn),
                    'mypos'      => new \App\Services\Banking\MyPosService($conn),
                    'eurobank'   => new \App\Services\Banking\EurobankService($conn),
                    'vivawallet' => new \App\Services\Banking\VivaWalletService($conn),
                    default      => null
                };

                if (!$service) {
                    $this->warn("   ⚠️ Skipping: Provider '{$conn->provider}' not implemented.");
                    continue;
                }

                // 1. Refresh Balances/Accounts first
                if (method_exists($service, 'refreshAccounts')) {
                    $service->refreshAccounts();
                } else if (method_exists($service, 'getAccounts')) {
                    $accounts = $service->getAccounts();
                    $conn->update(['credentials' => array_merge($conn->credentials, ['last_known_accounts' => $accounts])]);
                }

                // 2. Then Sync Transactions
                $count = $service->syncTransactions(
                    now()->subDays(14)->toDateString(), 
                    now()->toDateString()
                );

                $conn->update(['last_synced_at' => now()]);
                $this->info("   ✅ SUCCESS: Imported/Updated {$count} transactions.");

            } catch (\Exception $e) {
                $this->error("   ❌ ERROR: " . $e->getMessage());
            }
        }

        return 0;
    }
}
