<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Models\BankingConnection;

echo "--- 🛠️ BoC DEBUGGER: B2B POLLING FLOW (Reuse Subscription if available) ---\n\n";

// 1️⃣ Credentials (Sandbox)
$clientId     = 'b366fbbb0a2abd5fceae74dc9fa45416';
$clientSecret = '3557a8f4832d1fef2e35b7ef53dfd3e5';
$baseUrl      = 'https://sandbox-apis.bankofcyprus.com/df-boc-org-sb/sb';
$redirectUri  = 'https://call.witbo.com.cy/banking/callback/boc';

// 2️⃣ Get latest BankingConnection
$conn = BankingConnection::where('provider', 'boc')->latest()->first();
if (!$conn) die("❌ FATAL: No BoC connection found in database.\n");

$creds = $conn->credentials ?? [];
$subscriptionId = $creds['subscription_id'] ?? null;
$status         = $creds['subscription_status'] ?? null;

echo "✅ Using connection ID: {$conn->id}\n\n";

// 3️⃣ STEP 1: Obtain TPP Token
echo "1️⃣ Obtaining TPP Token (TPPOAuth2Security)... ";
$tppResponse = Http::asForm()->post("$baseUrl/psd2/oauth2/token", [
    'grant_type'    => 'client_credentials',
    'client_id'     => $clientId,
    'client_secret' => $clientSecret,
    'scope'         => 'TPPOAuth2Security',
]);

if (!$tppResponse->successful()) {
    die("\n❌ TPP Token Request Failed:\n" . $tppResponse->body() . "\n");
}

$tppToken = $tppResponse->json('access_token');
echo "✅ SUCCESS\n\n";

// 4️⃣ STEP 2: Create Subscription (Always New for debugging)
echo "2️⃣ Creating NEW Subscription... ";
$journeyId = (string) Str::uuid();
    $subResponse = Http::withToken($tppToken)
        ->withHeaders([
            'Content-Type' => 'application/json',
            'timeStamp'    => (string) time(),
            'journeyId'    => $journeyId,
        ])
        ->post("$baseUrl/psd2/v1/subscriptions", [
            'accounts' => [
                'transactionHistory'     => true,
                'balance'                => true,
                'details'                => true,
                'checkFundsAvailability' => true,
            ],
            'payments' => [
                'limit'    => 999999,
                'currency' => 'EUR',
                'amount'   => 999999,
            ],
        ]);

    if (!$subResponse->successful()) {
        die("\n❌ Subscription Creation Failed:\n" . $subResponse->body() . "\n");
    }

    $subscriptionId = $subResponse->json('subscriptionId');
    $status         = 'PENDING';
    echo "✅ SUCCESS (Subscription ID: $subscriptionId)\n\n";

    // Update DB
    $creds['subscription_id']     = $subscriptionId;
    $creds['subscription_status'] = $status;
    $creds['last_updated']        = now()->toDateTimeString();
    $conn->update(['credentials' => $creds]);
    echo "✅ Database updated with new subscription\n\n";
 

// 5️⃣ STEP 3: Generate state
$state = $creds['oauth_state'] ?? 'boc_' . Str::uuid();
session(['boc_oauth_state' => $state]);
$creds['oauth_state'] = $state;
$conn->update(['credentials' => $creds]);

// 6️⃣ STEP 4: Generate Authorization URL
$authUrl = "$baseUrl/psd2/oauth2/authorize?" . 
    "response_type=code" .
    "&redirect_uri=" . ($redirectUri) .
    "&scope=UserOAuth2Security" .
    "&client_id=" . $clientId .
    "&subscriptionid=" . $subscriptionId;

echo "--- 🚨 ACTION REQUIRED ---\n";
echo "Open this URL in your browser to authorize:\n\n$authUrl\n\n";
echo "----------------------------------------------------------------------\n\n";

// 7️⃣ STEP 5: Poll for subscription activation
echo "⌛ Polling for subscription activation (max 5 minutes)...\n";
$maxSeconds     = 300;
$secondsElapsed = 0;

while ($secondsElapsed < $maxSeconds) {
    $checkResponse = Http::withToken($tppToken)
        ->withHeaders([
            'timeStamp' => (string) time(),
            'journeyId' => (string) Str::uuid(),
        ])
        ->get("$baseUrl/psd2/v1/subscriptions/$subscriptionId");

    if ($checkResponse->successful()) {
        $status = $checkResponse->json('status');
        echo "   [" . date('H:i:s') . "] Status: $status\n";

        if ($status === 'ACTV') {
            echo "\n🎉 SUCCESS! Subscription is ACTIVE.\n";
            $creds['subscription_status'] = 'ACTV';
            $conn->update(['credentials' => $creds]);
            break;
        }

        if (in_array($status, ['REJ', 'REVOKED', 'FAILED'])) {
            echo "\n❌ Subscription failed. Status: $status\n";
            break;
        }
    } else {
        echo "   [" . date('H:i:s') . "] Warning: Check request failed - HTTP " . $checkResponse->status() . "\n";
    }

    sleep(5);
    $secondsElapsed += 5;
}

if ($status !== 'ACTV') {
    echo "\n⏰ Timeout reached. Final status: $status\n";
    echo "Make sure you completed the authorization in your browser.\n";
}

echo "\n--- End of BoC Debugger ---\n";