<?php

namespace Database\Seeders;

use App\Models\CallLog;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Quote;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceFeature;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class RealCompanySeeder extends Seeder
{
    public function run(): void
    {
        $this->seedNorthstarLive();
        $this->seedBlueHarborStudios();
    }

    protected function seedNorthstarLive(): void
    {
        $workspace = Workspace::updateOrCreate(
            ['company_name' => 'Northstar Live Events Ltd'],
            [
                'vat_number' => 'CY10458291Q',
                'tic_number' => 'TIC-LE-20491',
                'address' => '18 Spyrou Kyprianou Avenue, Limassol 3070, Cyprus',
                'phone' => '+357 25 816900',
                'email' => 'accounts@northstarlive.com',
                'iban' => 'CY21002001950000357001234567',
                'bic' => 'BCYPCY2N',
                'brand_color' => '#0F766E',
                'currency' => 'EUR',
                'invoice_prefix' => 'NSL-INV-',
                'next_invoice_number' => 3001,
                'quote_prefix' => 'NSL-Q-',
                'next_quote_number' => 3501,
            ]
        );

        $features = [
            'crm_basic' => true,
            'crm_reminders' => true,
            'inventory_basic' => true,
            'billing_standard' => true,
            'call_intelligence' => true,
            'expense_manager' => true,
            'asset_health' => true,
            'warehouse_workflow' => true,
        ];

        foreach ($features as $featureName => $isEnabled) {
            WorkspaceFeature::updateOrCreate(
                ['workspace_id' => $workspace->id, 'feature_name' => $featureName],
                ['is_enabled' => $isEnabled]
            );
        }

        $john = User::updateOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'current_workspace_id' => $workspace->id,
                'is_super_admin' => true,
            ]
        );
        $john->workspaces()->syncWithoutDetaching([$workspace->id => ['role' => 'owner']]);

        $ops = User::updateOrCreate(
            ['email' => 'operations@northstarlive.com'],
            [
                'name' => 'Elena Markou',
                'password' => Hash::make('password'),
                'current_workspace_id' => $workspace->id,
                'is_super_admin' => false,
            ]
        );
        $ops->workspaces()->syncWithoutDetaching([$workspace->id => ['role' => 'admin']]);

        $finance = User::updateOrCreate(
            ['email' => 'finance@northstarlive.com'],
            [
                'name' => 'Adrian Petrou',
                'password' => Hash::make('password'),
                'current_workspace_id' => $workspace->id,
                'is_super_admin' => false,
            ]
        );
        $finance->workspaces()->syncWithoutDetaching([$workspace->id => ['role' => 'admin']]);

        $warehouse = User::updateOrCreate(
            ['email' => 'warehouse@northstarlive.com'],
            [
                'name' => 'Mihai Luca',
                'password' => Hash::make('password'),
                'current_workspace_id' => $workspace->id,
                'is_super_admin' => false,
            ]
        );
        $warehouse->workspaces()->syncWithoutDetaching([$workspace->id => ['role' => 'staff']]);

        $audio = ProductCategory::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Audio Systems'],
            ['workspace_id' => $workspace->id]
        );
        $lighting = ProductCategory::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Lighting'],
            ['workspace_id' => $workspace->id]
        );
        $staging = ProductCategory::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Staging & Rigging'],
            ['workspace_id' => $workspace->id]
        );
        $services = ProductCategory::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Production Services'],
            ['workspace_id' => $workspace->id]
        );

        $products = [
            'line_array' => Product::updateOrCreate(
                ['workspace_id' => $workspace->id, 'sku' => 'AUD-LACOUSTICS-K2'],
                [
                    'name' => 'L-Acoustics K2 Line Array Cabinet',
                    'product_type' => 'physical',
                    'product_category_id' => $audio->id,
                    'unit_price_gross' => 380.00,
                    'vat_rate' => 19.00,
                    'current_stock' => 16,
                    'purchase_price' => 24000.00,
                    'acquisition_date' => '2024-02-10',
                ]
            ),
            'digital_console' => Product::updateOrCreate(
                ['workspace_id' => $workspace->id, 'sku' => 'AUD-DIGICO-S21'],
                [
                    'name' => 'DiGiCo S21 Digital Console',
                    'product_type' => 'physical',
                    'product_category_id' => $audio->id,
                    'unit_price_gross' => 450.00,
                    'vat_rate' => 19.00,
                    'current_stock' => 2,
                    'purchase_price' => 6400.00,
                    'acquisition_date' => '2023-11-18',
                ]
            ),
            'moving_head' => Product::updateOrCreate(
                ['workspace_id' => $workspace->id, 'sku' => 'LGT-ROBE-PAINTE'],
                [
                    'name' => 'Robe Painte Moving Head',
                    'product_type' => 'physical',
                    'product_category_id' => $lighting->id,
                    'unit_price_gross' => 125.00,
                    'vat_rate' => 19.00,
                    'current_stock' => 24,
                    'purchase_price' => 1950.00,
                    'acquisition_date' => '2024-04-05',
                ]
            ),
            'truss' => Product::updateOrCreate(
                ['workspace_id' => $workspace->id, 'sku' => 'STG-TRUSS-29CM'],
                [
                    'name' => '290mm Aluminum Truss 3m',
                    'product_type' => 'physical',
                    'product_category_id' => $staging->id,
                    'unit_price_gross' => 38.00,
                    'vat_rate' => 19.00,
                    'current_stock' => 60,
                    'purchase_price' => 210.00,
                    'acquisition_date' => '2022-09-14',
                ]
            ),
            'crew_day' => Product::updateOrCreate(
                ['workspace_id' => $workspace->id, 'sku' => 'SRV-TECH-DAY'],
                [
                    'name' => 'Senior Event Technician Day Rate',
                    'product_type' => 'service',
                    'product_category_id' => $services->id,
                    'unit_price_gross' => 285.00,
                    'vat_rate' => 19.00,
                    'current_stock' => 999,
                    'purchase_price' => 0.00,
                    'acquisition_date' => '2025-01-01',
                ]
            ),
        ];

        $this->seedNorthstarCustomerPortfolio($workspace, $products);

        $expenses = [
            ['category' => 'fuel', 'amount' => 428.90, 'expense_date' => Carbon::now()->subDays(7), 'vendor_name' => 'Petrolina Fleet Card'],
            ['category' => 'sub_rental', 'amount' => 1850.00, 'expense_date' => Carbon::now()->subDays(5), 'vendor_name' => 'Cyprus LED Walls Ltd'],
            ['category' => 'staff_wages', 'amount' => 4120.00, 'expense_date' => Carbon::now()->subDays(2), 'vendor_name' => 'Northstar Payroll'],
            ['category' => 'utility', 'amount' => 265.40, 'expense_date' => Carbon::now()->subDay(), 'vendor_name' => 'EPA Warehouse Service'],
        ];

        foreach ($expenses as $expense) {
            Expense::updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'vendor_name' => $expense['vendor_name'],
                    'amount' => $expense['amount'],
                ],
                [
                    'category' => $expense['category'],
                    'expense_date' => $expense['expense_date'],
                    'receipt_url' => null,
                ]
            );
        }
    }

    protected function seedBlueHarborStudios(): void
    {
        $workspace = Workspace::updateOrCreate(
            ['company_name' => 'Blue Harbor Studios'],
            [
                'vat_number' => 'MT24819023',
                'tic_number' => 'TIC-ME-88210',
                'address' => '22 Marina Wharf, Gzira GZR 1134, Malta',
                'phone' => '+356 2134 8821',
                'email' => 'finance@blueharborstudios.mt',
                'iban' => 'MT84MALT011000012345MTLCAST001S',
                'bic' => 'MALTMTMT',
                'brand_color' => '#1D4ED8',
                'currency' => 'EUR',
                'invoice_prefix' => 'BHS-INV-',
                'next_invoice_number' => 231,
                'quote_prefix' => 'BHS-Q-',
                'next_quote_number' => 89,
            ]
        );

        foreach ([
            'crm_basic' => true,
            'crm_reminders' => true,
            'inventory_basic' => true,
            'billing_standard' => true,
            'call_intelligence' => false,
            'expense_manager' => true,
            'asset_health' => false,
            'warehouse_workflow' => false,
        ] as $featureName => $isEnabled) {
            WorkspaceFeature::updateOrCreate(
                ['workspace_id' => $workspace->id, 'feature_name' => $featureName],
                ['is_enabled' => $isEnabled]
            );
        }

        $owner = User::updateOrCreate(
            ['email' => 'founder@blueharborstudios.mt'],
            [
                'name' => 'Isabella Vella',
                'password' => Hash::make('password'),
                'current_workspace_id' => $workspace->id,
                'is_super_admin' => false,
            ]
        );
        $owner->workspaces()->syncWithoutDetaching([$workspace->id => ['role' => 'owner']]);

        $editor = User::updateOrCreate(
            ['email' => 'producer@blueharborstudios.mt'],
            [
                'name' => 'Luca Borg',
                'password' => Hash::make('password'),
                'current_workspace_id' => $workspace->id,
                'is_super_admin' => false,
            ]
        );
        $editor->workspaces()->syncWithoutDetaching([$workspace->id => ['role' => 'staff']]);

        $media = ProductCategory::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Media Production'],
            ['workspace_id' => $workspace->id]
        );

        $studio = ProductCategory::updateOrCreate(
            ['workspace_id' => $workspace->id, 'name' => 'Studio Hire'],
            ['workspace_id' => $workspace->id]
        );

        $cameraPackage = Product::updateOrCreate(
            ['workspace_id' => $workspace->id, 'sku' => 'MED-FX6-KIT'],
            [
                'name' => 'Sony FX6 Camera Package',
                'product_type' => 'physical',
                'product_category_id' => $media->id,
                'unit_price_gross' => 320.00,
                'vat_rate' => 18.00,
                'current_stock' => 3,
                'purchase_price' => 7800.00,
                'acquisition_date' => '2024-06-11',
            ]
        );

        $editingDay = Product::updateOrCreate(
            ['workspace_id' => $workspace->id, 'sku' => 'SRV-POST-DAY'],
            [
                'name' => 'Senior Editor Day Rate',
                'product_type' => 'service',
                'product_category_id' => $media->id,
                'unit_price_gross' => 260.00,
                'vat_rate' => 18.00,
                'current_stock' => 999,
                'purchase_price' => 0.00,
                'acquisition_date' => '2025-01-01',
            ]
        );

        $studioDay = Product::updateOrCreate(
            ['workspace_id' => $workspace->id, 'sku' => 'STU-CYCLORAMA-DAY'],
            [
                'name' => 'Cyclorama Studio Day Hire',
                'product_type' => 'service',
                'product_category_id' => $studio->id,
                'unit_price_gross' => 680.00,
                'vat_rate' => 18.00,
                'current_stock' => 999,
                'purchase_price' => 0.00,
                'acquisition_date' => '2025-01-01',
            ]
        );

        $contact = Contact::updateOrCreate(
            ['workspace_id' => $workspace->id, 'email' => 'brand@solisretail.com'],
            $this->contactAttributes(
                name: 'Nina Caruana',
                companyName: 'Solis Retail Group',
                mobileNumber: '+356 9941 7720',
                vatNumber: 'MT20991021',
                address: '58 Merchants Street, Valletta VLT 1171, Malta',
                generalInfo: 'Monthly content production retainer for e-commerce launches and seasonal campaigns.'
            )
        );

        $this->createInvoice(
            workspace: $workspace,
            contact: $contact,
            invoiceNumber: 'BHS-INV-229',
            date: Carbon::now()->subDays(12),
            dueDate: Carbon::now()->subDays(1),
            status: 'paid',
            items: [
                ['product' => $studioDay, 'description' => 'Studio hire for SS26 campaign', 'quantity' => 2],
                ['product' => $cameraPackage, 'description' => 'Cinema camera kit rental', 'quantity' => 2],
                ['product' => $editingDay, 'description' => 'Post-production and short-form edits', 'quantity' => 3],
            ],
            payments: [
                ['amount' => 3536.00, 'payment_method' => 'bank_transfer', 'reference' => 'SOLIS-MAR-SETTLED'],
            ]
        );

        $this->createQuote(
            workspace: $workspace,
            contact: $contact,
            quoteNumber: 'BHS-Q-0088',
            date: Carbon::now()->subDays(4),
            validUntil: Carbon::now()->addDays(10),
            status: 'accepted',
            items: [
                ['product' => $studioDay, 'description' => 'Studio hire for summer accessories campaign', 'quantity' => 1],
                ['product' => $cameraPackage, 'description' => 'Cinema camera and lens package', 'quantity' => 1],
                ['product' => $editingDay, 'description' => 'Post-production master edit', 'quantity' => 2],
            ]
        );

        Expense::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'vendor_name' => 'Studio Electricity Account',
                'amount' => 198.50,
            ],
            [
                'category' => 'utility',
                'expense_date' => Carbon::now()->subDays(6),
                'receipt_url' => null,
            ]
        );
    }

    protected function createInvoice(
        Workspace $workspace,
        Contact $contact,
        string $invoiceNumber,
        Carbon $date,
        Carbon $dueDate,
        string $status,
        array $items,
        array $payments
    ): Invoice {
        $totals = $this->calculateTotals($items);

        $invoice = Invoice::updateOrCreate(
            ['workspace_id' => $workspace->id, 'invoice_number' => $invoiceNumber],
            [
                'contact_id' => $contact->id,
                'doc_type' => 'invoice',
                'date' => $date->toDateString(),
                'due_date' => $dueDate->toDateString(),
                'status' => $status,
                'currency' => $workspace->currency,
                'exchange_rate' => 1.00000,
                'subtotal_net' => $totals['subtotal_net'],
                'total_vat_amount' => $totals['total_vat_amount'],
                'grand_total_gross' => $totals['grand_total_gross'],
                'amount_paid' => collect($payments)->sum('amount'),
                'balance_due' => max($totals['grand_total_gross'] - collect($payments)->sum('amount'), 0),
            ]
        );

        DB::table('invoice_items')->where('invoice_id', $invoice->id)->delete();

        foreach ($items as $item) {
            $product = $item['product'];
            $quantity = (float) $item['quantity'];
            $unitPriceNet = round($product->unit_price_gross / (1 + ($product->vat_rate / 100)), 4);
            $totalGross = round($quantity * $unitPriceNet * (1 + ($product->vat_rate / 100)), 2);

            DB::table('invoice_items')->insert([
                'product_id' => $product->id,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price_net' => $unitPriceNet,
                'vat_rate' => $product->vat_rate,
                'total_gross' => $totalGross,
                'invoice_id' => $invoice->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('payments')->where('invoice_id', $invoice->id)->delete();

        foreach ($payments as $index => $payment) {
            DB::table('payments')->insert([
                'invoice_id' => $invoice->id,
                'amount' => $payment['amount'],
                'payment_method' => $payment['payment_method'],
                'payment_date' => $date->copy()->addDays($index + 2),
                'reference' => $payment['reference'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $invoice;
    }

    protected function seedNorthstarCustomerPortfolio(Workspace $workspace, array $products): void
    {
        $customers = $this->northstarCustomerDefinitions();
        $invoiceStatuses = ['paid', 'paid', 'partial', 'unpaid', 'paid', 'unpaid', 'partial', 'paid', 'unpaid', 'paid'];
        $quoteStatuses = ['draft', 'sent', 'viewed', 'accepted', 'expired'];

        foreach ($customers as $customerIndex => $customer) {
            $contact = Contact::updateOrCreate(
                ['workspace_id' => $workspace->id, 'email' => $customer['email']],
                $this->contactAttributes(
                    name: $customer['name'],
                    companyName: $customer['company_name'],
                    mobileNumber: $customer['mobile_number'],
                    vatNumber: $customer['vat_number'],
                    address: $customer['address'],
                    generalInfo: $customer['general_info']
                )
            );

            for ($reminderIndex = 1; $reminderIndex <= 2; $reminderIndex++) {
                Reminder::updateOrCreate(
                    [
                        'contact_id' => $contact->id,
                        'title' => $this->reminderTitle($reminderIndex, $customer['company_name']),
                    ],
                    [
                        'remind_at' => Carbon::now()->addDays(($customerIndex % 10) + ($reminderIndex * 3)),
                    ]
                );
            }

            for ($callIndex = 1; $callIndex <= 2; $callIndex++) {
                CallLog::updateOrCreate(
                    [
                        'contact_id' => $contact->id,
                        'call_date' => Carbon::now()->subDays(($customerIndex % 14) + ($callIndex * 2))->setTime(9 + $callIndex, 15),
                    ],
                    [
                        'invoice_id' => null,
                        'call_type' => $callIndex % 2 === 0 ? 'inbound' : 'outbound',
                        'call_duration_seconds' => 480 + ($customerIndex * 7) + ($callIndex * 90),
                        'call_notes' => $this->callNotes($customer['company_name'], $callIndex),
                        'call_recording_url' => null,
                    ]
                );
            }

            for ($invoiceIndex = 1; $invoiceIndex <= 10; $invoiceIndex++) {
                $items = $this->buildSeedItems($products, $customerIndex, $invoiceIndex);
                $totals = $this->calculateTotals($items);
                $status = $invoiceStatuses[$invoiceIndex - 1];
                $date = Carbon::now()->subDays(($customerIndex * 11) + ($invoiceIndex * 3));

                $this->createInvoice(
                    workspace: $workspace,
                    contact: $contact,
                    invoiceNumber: sprintf('NSL-INV-%04d', 2000 + (($customerIndex - 1) * 10) + $invoiceIndex),
                    date: $date,
                    dueDate: $date->copy()->addDays(14),
                    status: $status,
                    items: $items,
                    payments: $this->paymentPlanForStatus($status, $totals['grand_total_gross'], $customerIndex, $invoiceIndex)
                );
            }

            for ($quoteIndex = 1; $quoteIndex <= 5; $quoteIndex++) {
                $this->createQuote(
                    workspace: $workspace,
                    contact: $contact,
                    quoteNumber: sprintf('NSL-Q-%04d', 3000 + (($customerIndex - 1) * 5) + $quoteIndex),
                    date: Carbon::now()->subDays(($customerIndex * 5) + $quoteIndex),
                    validUntil: Carbon::now()->addDays(7 + ($quoteIndex * 3)),
                    status: $quoteStatuses[$quoteIndex - 1],
                    items: $this->buildSeedItems($products, $customerIndex, 100 + $quoteIndex)
                );
            }
        }
    }

    protected function calculateTotals(array $items): array
    {
        $subtotalNet = 0.00;
        $totalVat = 0.00;

        foreach ($items as $item) {
            $product = $item['product'];
            $quantity = (float) $item['quantity'];
            $unitPriceNet = $product->unit_price_gross / (1 + ($product->vat_rate / 100));
            $lineNet = $quantity * $unitPriceNet;
            $lineVat = $lineNet * ($product->vat_rate / 100);

            $subtotalNet += $lineNet;
            $totalVat += $lineVat;
        }

        return [
            'subtotal_net' => round($subtotalNet, 2),
            'total_vat_amount' => round($totalVat, 2),
            'grand_total_gross' => round($subtotalNet + $totalVat, 2),
        ];
    }

    protected function createQuote(
        Workspace $workspace,
        Contact $contact,
        string $quoteNumber,
        Carbon $date,
        Carbon $validUntil,
        string $status,
        array $items
    ): Quote {
        $totals = $this->calculateTotals($items);

        $quote = Quote::updateOrCreate(
            ['workspace_id' => $workspace->id, 'quote_number' => $quoteNumber],
            [
                'contact_id' => $contact->id,
                'date' => $date->toDateString(),
                'valid_until' => $validUntil->toDateString(),
                'status' => $status,
                'discount' => 0,
                'subtotal_net' => $totals['subtotal_net'],
                'total_vat_amount' => $totals['total_vat_amount'],
                'grand_total_gross' => $totals['grand_total_gross'],
                'notes' => 'Prepared from seeded realistic company data.',
                'terms' => 'Net 14 days. Equipment availability subject to booking confirmation.',
            ]
        );

        DB::table('quote_items')->where('quote_id', $quote->id)->delete();

        foreach ($items as $item) {
            $product = $item['product'];
            $quantity = (float) $item['quantity'];
            $unitPriceNet = round($product->unit_price_gross / (1 + ($product->vat_rate / 100)), 4);
            $totalGross = round($quantity * $unitPriceNet * (1 + ($product->vat_rate / 100)), 2);

            DB::table('quote_items')->insert([
                'product_id' => $product->id,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price_net' => $unitPriceNet,
                'vat_rate' => $product->vat_rate,
                'total_gross' => $totalGross,
                'quote_id' => $quote->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $quote;
    }

    protected function buildSeedItems(array $products, int $contactIndex, int $documentIndex): array
    {
        $productPool = array_values($products);
        $items = [];

        for ($offset = 0; $offset < 3; $offset++) {
            $product = $productPool[($contactIndex + $documentIndex + $offset) % count($productPool)];
            $quantity = $product->product_type === 'service'
                ? (($contactIndex + $documentIndex + $offset) % 4) + 1
                : (($contactIndex + $documentIndex + $offset) % 12) + 2;

            $items[] = [
                'product' => $product,
                'description' => $this->lineDescription($product->name, $offset),
                'quantity' => $quantity,
            ];
        }

        return $items;
    }

    protected function paymentPlanForStatus(string $status, float $grandTotalGross, int $contactIndex, int $invoiceIndex): array
    {
        if ($status === 'paid') {
            return [[
                'amount' => round($grandTotalGross, 2),
                'payment_method' => 'bank_transfer',
                'reference' => sprintf('NSL-PAY-%03d-%02d', $contactIndex, $invoiceIndex),
            ]];
        }

        if ($status === 'partial') {
            return [[
                'amount' => round($grandTotalGross * 0.45, 2),
                'payment_method' => 'bank_transfer',
                'reference' => sprintf('NSL-DEP-%03d-%02d', $contactIndex, $invoiceIndex),
            ]];
        }

        return [];
    }

    protected function lineDescription(string $productName, int $offset): string
    {
        $suffixes = [
            'for venue production package',
            'including onsite deployment',
            'with show-day technical support',
        ];

        return $productName . ' ' . $suffixes[$offset % count($suffixes)];
    }

    protected function reminderTitle(int $index, string $companyName): string
    {
        $titles = [
            'Confirm next booking window with ',
            'Send revised commercial offer to ',
        ];

        return $titles[$index - 1] . $companyName;
    }

    protected function callNotes(string $companyName, int $callIndex): string
    {
        $notes = [
            'Reviewed event requirements, venue load-in timing, and production staffing for ',
            'Discussed pricing updates, optional upgrades, and approval timing with ',
        ];

        return $notes[$callIndex - 1] . $companyName . '.';
    }

    protected function northstarCustomerDefinitions(): array
    {
        $customers = [
            [
                'name' => 'Sofia Andreou',
                'company_name' => 'Azura Marina Resort',
                'email' => 'events@azuramarinaresort.com',
                'mobile_number' => '+357 99 120843',
                'vat_number' => 'CY10918275F',
                'address' => '14 Poseidonos Road, Paphos 8042, Cyprus',
                'general_info' => 'Key hospitality client. Prefers turnkey stage, audio, and gala dinner packages.',
            ],
            [
                'name' => 'Daniel Weber',
                'company_name' => 'NovaTech Expo Europe',
                'email' => 'procurement@novatechexpo.eu',
                'mobile_number' => '+49 151 40022010',
                'vat_number' => 'DE318920114',
                'address' => '7 Friedrichstrasse, Berlin 10117, Germany',
                'general_info' => 'Annual expo client requiring dry hire and technical crew for 3-day conventions.',
            ],
            [
                'name' => 'Marta Stoica',
                'company_name' => 'Citrus Mobile',
                'email' => 'marketing@citrusmobile.com',
                'mobile_number' => '+40 723 440901',
                'vat_number' => 'RO44182910',
                'address' => '112 Calea Dorobanti, Bucharest 010576, Romania',
                'general_info' => 'Launch events and roadshow activations across Cyprus and Greece.',
            ],
            [
                'name' => 'Petros Demetriou',
                'company_name' => 'Limassol Arena',
                'email' => 'facilities@limassolarena.com',
                'mobile_number' => '+357 96 228740',
                'vat_number' => 'CY11273488K',
                'address' => '88 Franklin Roosevelt Street, Limassol 3012, Cyprus',
                'general_info' => 'Venue partner. Usually books truss, staging, and lighting maintenance support.',
            ],
        ];

        $firstNames = ['Alex', 'Mia', 'Noah', 'Lina', 'Owen', 'Eva', 'Leon', 'Iris', 'Theo', 'Nadia', 'Marco', 'Sara'];
        $lastNames = ['Petrescu', 'Iacovou', 'Schmidt', 'Vella', 'Georgiou', 'Costa', 'Nowak', 'Marin', 'Ivanov', 'Pereira'];
        $prefixes = ['Apex', 'Atlas', 'Harbor', 'Summit', 'Lighthouse', 'Silverline', 'Cobalt', 'Orchid', 'Prime', 'Aurora'];
        $sectors = ['Resort', 'Conference Center', 'Expo Group', 'Hospitality Group', 'Retail Brand', 'Arena Services', 'Festival Office', 'Corporate Events'];
        $cities = ['Limassol', 'Nicosia', 'Paphos', 'Larnaca', 'Athens', 'Bucharest', 'Berlin', 'Valletta', 'Sofia', 'Thessaloniki'];

        for ($index = 5; $index <= 100; $index++) {
            $firstName = $firstNames[($index - 5) % count($firstNames)];
            $lastName = $lastNames[($index - 5) % count($lastNames)];
            $prefix = $prefixes[($index - 5) % count($prefixes)];
            $sector = $sectors[($index - 5) % count($sectors)];
            $city = $cities[($index - 5) % count($cities)];
            $companyName = $prefix . ' ' . $sector . ' ' . $index;
            $emailSlug = strtolower(str_replace(' ', '', $prefix . $sector . $index));

            $customers[] = [
                'name' => $firstName . ' ' . $lastName,
                'company_name' => $companyName,
                'email' => $emailSlug . '@example-client.com',
                'mobile_number' => '+357 97 ' . str_pad((string) (100000 + $index), 6, '0', STR_PAD_LEFT),
                'vat_number' => 'CY' . (11000000 + $index) . chr(65 + ($index % 26)),
                'address' => $index . ' Commerce Avenue, ' . $city . ', Cyprus',
                'general_info' => $companyName . ' books staging, lighting, and technical crew for seasonal events and activations.',
            ];
        }

        return $customers;
    }

    protected function contactAttributes(
        string $name,
        string $companyName,
        string $mobileNumber,
        string $vatNumber,
        string $address,
        string $generalInfo
    ): array {
        $attributes = [
            'name' => $name,
            'mobile_number' => $mobileNumber,
            'vat_number' => $vatNumber,
            'general_info' => $generalInfo,
        ];

        if (Schema::hasColumn('contacts', 'company_name')) {
            $attributes['company_name'] = $companyName;
        } else {
            $attributes['general_info'] = $companyName . '. ' . $generalInfo;
        }

        if (Schema::hasColumn('contacts', 'address')) {
            $attributes['address'] = $address;
        } else {
            $attributes['general_info'] .= ' Address: ' . $address . '.';
        }

        if (Schema::hasColumn('contacts', 'contact_type')) {
            $attributes['contact_type'] = 'customer';
        }

        return $attributes;
    }
}
