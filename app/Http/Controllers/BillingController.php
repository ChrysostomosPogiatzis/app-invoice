<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Models\SubscriptionPayment;
use App\Services\TierService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mypos\IPC\Config;
use Mypos\IPC\Purchase;
use Mypos\IPC\Cart;
use Mypos\IPC\Customer;
use Mypos\IPC\Response;

class BillingController extends Controller
{
    /**
     * Create a myPOS Checkout Session using the official SDK and Configuration Package.
     */
    public function createCheckoutSession(Request $request)
    {
        $user = auth()->user();
        $workspace = $user->currentWorkspaceRecord();
        
        if (!$workspace) {
            return back()->with('error', 'Operation failed: No active business node found.');
        }

        $price = TierService::getPrice($workspace->tier);

        try {
            // 1. Setup the SDK Config from the provided Configuration Package
            $cnf = new Config();
            $cnf->setIpcURL('https://www.mypos.eu/vmp/checkout');
            $cnf->setLang('en');
            $cnf->setVersion('1.4');
            $cnf->loadConfigurationPackage(env('MYPOS_CONFIG_PACKAGE'));

            // 2. Setup the Purchase
            $purchase = new Purchase($cnf);
            $purchase->setOrderID('REN-' . $workspace->id . '-' . time());
            $purchase->setCurrency('EUR');
            $purchase->setCardTokenRequest(Purchase::CARD_TOKEN_REQUEST_NONE);
            $purchase->setPaymentParametersRequired(Purchase::PURCHASE_TYPE_SIMPLIFIED_PAYMENT_PAGE);
            
            // 3. URLs for redirection and notification
            $purchase->setUrlOk(route('billing.success'));
            $purchase->setUrlCancel(route('billing.cancel'));
            $purchase->setUrlNotify(route('billing.webhook'));

            // 4. Cart details
            $cart = new Cart();
            $cart->add($workspace->company_name . ' - ' . ucfirst($workspace->tier) . ' Renewal', 1, $price);
            $purchase->setCart($cart);

            // 5. Customer details (Required by SDK Process)
            $customer = new Customer();
            $customer->setEmail($workspace->email ?? $user->email);
            $customer->setFirstName($user->name);
            $customer->setLastName('Member');
            $purchase->setCustomer($customer);

            // 6. User identification
            $purchase->setNote('Workspace ID: ' . $workspace->id);

            // 6. Process the purchase (This will output an auto-submitting form and redirect)
            echo $purchase->process();
            exit;

        } catch (\Exception $e) {
            Log::error('myPOS SDK Exception', ['msg' => $e->getMessage()]);
            return back()->with('error', 'Unexpected Payment Gateway Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle incoming webhook from myPOS using SDK validation.
     */
    public function handleWebhook(Request $request)
    {
        try {
            $cnf = new Config();
            $cnf->setIpcURL('https://mypos.com/vmp/checkout/');
            $cnf->setLang('en');
            $cnf->setVersion('1.4');
            $cnf->loadConfigurationPackage(env('MYPOS_CONFIG_PACKAGE'));

            // Use the SDK to validate the incoming request signature
            $response = new Response($cnf);
            
            if ($response->isNewPayment()) {
                $data = $response->getData();
                $orderId = (string) ($data['OrderID'] ?? '');
                $workspaceId = $this->extractWorkspaceIdFromOrderId($orderId);

                if (! $workspaceId) {
                    Log::error('myPOS Webhook: Could not extract Workspace ID from OrderID: ' . $orderId);
                    return response('Invalid Order ID', 400);
                }

                $workspace = Workspace::find($workspaceId);
                if (!$workspace) {
                    Log::error('myPOS Webhook: Workspace not found for ID ' . $workspaceId);
                    return response('Workspace Not Found', 404);
                }

                if (SubscriptionPayment::where('gateway_order_id', $orderId)->exists()) {
                    Log::info('myPOS Webhook duplicate ignored', [
                        'order_id' => $orderId,
                        'workspace_id' => $workspace->id,
                    ]);

                    return response('OK');
                }

                DB::transaction(function () use ($workspace, $data, $orderId) {
                    $currentEnd = $workspace->trial_ends_at ? Carbon::parse($workspace->trial_ends_at) : Carbon::now();
                    $newEnd = $currentEnd->isPast() ? Carbon::now()->addDays(30) : $currentEnd->copy()->addDays(30);

                    $workspace->update([
                        'trial_ends_at' => $newEnd,
                        'last_billed_at' => Carbon::now(),
                        'is_active' => true
                    ]);

                    SubscriptionPayment::create([
                        'workspace_id' => $workspace->id,
                        'amount' => (float) ($data['Amount'] ?? 0),
                        'payment_method' => 'mypos',
                        'gateway_order_id' => $orderId,
                        'billed_at' => Carbon::now(),
                        'extended_until' => $newEnd,
                        'notes' => 'Automated SDK-Verified Renewal. Order: ' . $orderId
                    ]);
                });

                Log::info('myPOS Webhook processed', [
                    'order_id' => $orderId,
                    'workspace_id' => $workspace->id,
                    'amount' => (float) ($data['Amount'] ?? 0),
                ]);

                return response('OK');
            }

            return response('Unhandled event', 200);

        } catch (\Exception $e) {
            Log::error('myPOS Webhook Exception', ['msg' => $e->getMessage()]);
            return response('Error', 500);
        }
    }

    protected function extractWorkspaceIdFromOrderId(string $orderId): ?int
    {
        if (! preg_match('/^REN-(\d+)-(\d{10,})$/', $orderId, $matches)) {
            return null;
        }

        return (int) $matches[1];
    }
}
