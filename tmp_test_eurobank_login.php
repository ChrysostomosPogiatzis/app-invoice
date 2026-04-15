<?php

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

// Initialize Laravel for HTTP Client
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

/**
 * FINAL CLI TEST SCRIPT FOR EUROBANK CYPRUS SANDBOX
 */

// 1. CONFIG
$clientId = 'd1dff031e41bad0a2677e149d0de187e';
$clientSecret = '833f3b687ec4f263fdf7ac26f059d264';
$redirectUri = 'https://call.witbo.com.cy/banking/callback/eurobank';

// SANDBOX PATHS
$tppTokenUrl  = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/tppdev/oauth2/token';
$consentUrl   = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/sandbox/v1/consents';
$userTokenUrl = 'https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/oauth-t24-test/oauth2/token';

echo "--- Eurobank Sandbox Test ---\n";

try {
    // STEP 1: Get TPP Token
    echo "1. Obtaining TPP Access Token... ";
    $tppResponse = Http::asForm()->post($tppTokenUrl, [
        'grant_type'    => 'client_credentials',
        'client_id'     => $clientId,
        'client_secret' => $clientSecret,
        'scope'         => 'account.setup',
    ]);

    if (!$tppResponse->successful()) {
        echo "FAILED\n" . $tppResponse->body() . "\n";
        exit;
    }
    $tppToken = $tppResponse->json('access_token');
    echo "OK\n";

    // STEP 2: Create Consent
    echo "2. Creating AIS Consent... ";
    $requestId = (string) Str::uuid();
    $consentResponse = Http::withToken($tppToken)
        ->withHeaders([
            'X-IBM-Client-Id' => $clientId,
            'X-Request-ID'    => $requestId,
            'TPP-Redirect-URI' => $redirectUri,
            'Accept'          => 'application/json',
            'Content-Type'    => 'application/json',
        ])->post($consentUrl, [
            'access' => [
                'allPsd2' => 'allAccounts',
            ],
            'recurringIndicator' => true,
            'validUntil' => now()->addDays(90)->format('Y-m-d'),
            'frequencyPerDay' => 4,
            'combinedServiceIndicator' => false,
        ]);

    if (!$consentResponse->successful()) {
        echo "FAILED\n" . $consentResponse->body() . "\n";
        exit;
    }
    $consentId = $consentResponse->json('consentId');
    $authUrl = $consentResponse->json('_links.scaRedirect.href');

    // Handle "redirectLink" placeholder in Sandbox
    if (!$authUrl || !filter_var($authUrl, FILTER_VALIDATE_URL)) {
        $authUrl = "https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/oauth-t24-test/oauth2/authorize?" . http_build_query([
            'response_type' => 'code',
            'client_id'     => $clientId,
            'scope'         => 'AISP',
            'redirect_uri'  => $redirectUri,
            'consent'       => $consentId,
        ]);
    }
    echo "OK (ID: $consentId)\n";

    // STEP 3: Redirect Link
    echo "\n3. COPY AND PASTE THIS URL INTO YOUR BROWSER:\n";
    echo "-------------------------------------------\n";
    echo $authUrl . "\n";
    echo "-------------------------------------------\n\n";

    echo "4. ENTER THE 'code' FROM THE REDIRECT URL: ";
    $handle = fopen ("php://stdin","r");
    $code = trim(fgets($handle));

    if (empty($code)) exit;

    // STEP 4: Exchange Code
    echo "\n5. Exchanging Code for User Token... ";
    $tokenResponse = Http::asForm()
        ->withHeaders(['consent' => $consentId])
        ->post($userTokenUrl, [
            'grant_type'    => 'authorization_code',
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'code'          => $code,
            'redirect_uri'  => $redirectUri,
            'scope'         => 'AISP', // Adding scope back during exchange
        ]);

    if (!$tokenResponse->successful()) {
        echo "FAILED\n" . $tokenResponse->body() . "\n";
        exit;
    }
    $userToken = $tokenResponse->json('access_token');
    echo "OK\n";

    // STEP 5: List Accounts
    echo "6. Final Test: Fetching Accounts... ";
    $accountsResponse = Http::withToken($userToken)
        ->withHeaders([
            'X-Request-ID'     => (string) Str::uuid(),
            'X-IBM-Client-Id'  => $clientId,
            'Consent-ID'       => $consentId,
            'TPP-Redirect-URI' => $redirectUri,
            'PSU-IP-Address'   => '127.0.0.1',
            'Accept'           => 'application/json',
        ])
        ->get('https://apigw.eurobank.com.cy/eurobank-cy/erb-apis/sandbox/v1/accounts');

    if ($accountsResponse->successful()) {
        echo "SUCCESS!\n";
        print_r($accountsResponse->json());
    } else {
        echo "FAILED\n" . $accountsResponse->body() . "\n";
    }

} catch (\Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
}
