<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\BankingConnection;
use App\Services\Banking\BankOfCyprusService;

echo "--- 🛠️ BoC TRANSACTION TESTER ---\n\n";

// 1. Get the latest BoC connection
$conn = BankingConnection::where('provider', 'boc')->latest()->first();
if (!$conn) {
    die("❌ FATAL: No BoC connection found in database.\n");
}

echo "✅ Using connection ID: {$conn->id} ({$conn->label})\n";
echo "📊 Current Status: " . ($conn->credentials['subscription_status'] ?? 'UNKNOWN') . "\n\n";

$service = new BankOfCyprusService($conn);

try {
    // 2. Fetch Accounts to verify access
    echo "1️⃣ Fetching Accounts... ";
    $accounts = $service->getAccounts();
    echo "✅ SUCCESS\n";
    
    foreach ($accounts as $acc) {
        $iban = $acc['iban'] ?? 'Unknown';
        $bal  = $acc['balance'] ?? '0.00';
        $cur  = $acc['currency'] ?? 'EUR';
        echo "   🏦 Account: $iban | Balance: $bal $cur\n";
    }

    // 3. Sync Transactions (last 3000 days to capture sandbox data)
    $from = now()->subDays(3000)->toDateString();
    $to   = now()->toDateString();
    
    echo "\n2️⃣ Syncing Transactions ($from to $to)... ";
    $count = $service->syncTransactions($from, $to);
    echo "✅ SUCCESS\n\n";
    echo "✨ Imported/Updated $count transactions in the database.\n";

} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    
    // If it's a 401/403, might need to check if activation actually finished
    if (str_contains($e->getMessage(), '403') || str_contains($e->getMessage(), '401')) {
        echo "\n💡 TIP: If you see 403 Forbidden, ensure you finished the authorization in the browser\n";
        echo "   and that the subscription polling in the previous script reached 'ACTV'.\n";
    }
}

echo "\n--- End of Transaction Tester ---\n";
