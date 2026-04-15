<?php

require __DIR__ . '/vendor/autoload.php';

use App\Models\BankingConnection;
use App\Services\Banking\EurobankService;

// Initialize Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * DEBUG SCRIPT: FETCH BALANCES FOR EXISTING EUROBANK CONNECTION
 */

// Find the latest Eurobank connection
$conn = BankingConnection::where('provider', 'eurobank')->latest()->first();

if (!$conn) {
    echo "No Eurobank connection found in database.\n";
    exit;
}

echo "Testing Connection ID: {$conn->id} ({$conn->label})\n";
echo "Provider: {$conn->provider}\n";
echo "Active: " . ($conn->is_active ? 'YES' : 'NO') . "\n";
echo "-------------------------------------------\n";

try {
    $service = new EurobankService($conn);
    
    echo "Fetching Accounts & Balances...\n";
    $accounts = $service->getAccounts();
    
    if (empty($accounts)) {
        echo "No accounts returned or fetch failed (check logs).\n";
    } else {
        echo "Found " . count($accounts) . " accounts:\n\n";
        foreach ($accounts as $acc) {
            echo "Name:     " . ($acc['name'] ?? 'N/A') . "\n";
            echo "IBAN:     " . ($acc['iban'] ?? 'N/A') . "\n";
            echo "resourceId: " . ($acc['resourceId'] ?? 'N/A') . "\n";
            echo "Balance:  " . ($acc['balance'] ?? '0') . "\n";
            echo "-------------------------------------------\n";
            
            // Try fetching transactions for this account
            if ($acc['resourceId']) {
                $dateFrom = now()->subDays(180)->format('Y-m-d');
                echo "Testing Transactions for {$acc['resourceId']} (from {$dateFrom})...\n";
                $count = $service->syncTransactions($dateFrom); 
                echo "Synced {$count} transactions.\n";
            }
        }
    }

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
