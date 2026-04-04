<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Workspace;
use App\Models\WorkspaceFeature;
use App\Models\User;
use App\Models\Expense;
use App\Models\BankingConnection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create precisely 1 Primary Workspace
        $workspace = Workspace::updateOrCreate(
            ['company_name' => 'Elite Rental ERP'],
            [
                'vat_number' => 'EU987654321',
                'currency' => 'EUR',
                'address' => '123 Business Avenue, Limassol, Cyprus',
            ]
        );

        // 2. Create precisely 1 User
        $user = User::updateOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'name' => 'John Doe',
                'password' => Hash::make('password'),
                'is_super_admin' => true,
            ]
        );
        
        // Ensure user is only attached to this single workspace
        $user->workspaces()->sync([$workspace->id => ['role' => 'owner']]);
        $user->update(['current_workspace_id' => $workspace->id]);

        // 3. Seed Essential Features
        $features = [
            'crm_basic' => true,
            'inventory_basic' => true,
            'billing_standard' => true,
            'call_intelligence' => true,
            'crm_reminders' => true,
            'expense_manager' => true,
            'asset_health' => true,
            'warehouse_workflow' => false,
        ];

        foreach ($features as $name => $enabled) {
            WorkspaceFeature::updateOrCreate(
                ['workspace_id' => $workspace->id, 'feature_name' => $name],
                ['is_enabled' => $enabled]
            );
        }

        // 4. Create Sample Contacts
        $contactsData = [
            ['name' => 'Royal Plaza Hotel', 'company_name' => 'Royal Plaza', 'email' => 'booking@royalplaza.cy'],
            ['name' => 'City Conference Center', 'company_name' => 'CCC Cyprus', 'email' => 'info@ccc.com.cy'],
        ];

        foreach ($contactsData as $c) {
            Contact::updateOrCreate(
                ['email' => $c['email'], 'workspace_id' => $workspace->id],
                $c
            );
        }
        $allContacts = Contact::where('workspace_id', $workspace->id)->get();

        // 5. Create Sample Products
        $productsData = [
            ['name' => 'Power Generator 250kVA', 'sku' => 'PWR-GEN-01', 'unit_price_net' => 450.00, 'purchase_price' => 15000.00, 'current_stock' => 2, 'product_type' => 'physical'],
            ['name' => 'Outdoor Stage Mesh 10x8m', 'sku' => 'STG-MSH-10', 'unit_price_net' => 2200.00, 'purchase_price' => 24000.00, 'current_stock' => 1, 'product_type' => 'physical'],
            ['name' => 'Standard Black Chair', 'sku' => 'CHR-STD-BLK', 'unit_price_net' => 2.50, 'purchase_price' => 15.00, 'current_stock' => 500, 'product_type' => 'physical'],
        ];

        foreach ($productsData as $p) {
            Product::updateOrCreate(
                ['sku' => $p['sku'], 'workspace_id' => $workspace->id],
                array_merge($p, ['vat_rate' => 19.00])
            );
        }
        $allProducts = Product::where('workspace_id', $workspace->id)->get();

        // 6. Create Sample Invoices
        $invoice = Invoice::updateOrCreate(
            ['invoice_number' => 'INV-2026-001', 'workspace_id' => $workspace->id],
            [
                'contact_id' => $allContacts[0]->id,
                'date' => Carbon::now()->subDays(5),
                'due_date' => Carbon::now()->addDays(10),
                'subtotal_net' => 1500.00,
                'total_vat_amount' => 285.00,
                'discount' => 0.00,
                'grand_total_gross' => 1785.00,
                'amount_paid' => 1785.00,
                'balance_due' => 0.00,
                'status' => 'paid',
                'doc_type' => 'invoice'
            ]
        );

        if ($invoice->items()->count() === 0) {
            $invoice->items()->create([
                'description' => 'Annual Logistics and Equipment Maintenance Support',
                'quantity' => 1,
                'unit_price_net' => 1500.00,
                'vat_rate' => 19.00,
                'total_gross' => 1785.00
            ]);
        }

        // 7. Seed Expenses
        Expense::updateOrCreate(
            ['vendor_name' => 'Fibre Link Pay', 'workspace_id' => $workspace->id, 'amount' => 120.00],
            [
                'category' => 'other',
                'expense_date' => Carbon::now()->subDays(1),
                'vendor_name' => 'Fibre Link Pay',
            ]
        );

        // 8. Seed Banking Connections (using demo data format)
        BankingConnection::updateOrCreate(
            ['provider' => 'vivawallet', 'workspace_id' => $workspace->id],
            [
                'label' => 'Viva Wallet Business Account',
                'is_active' => true,
                'credentials' => [
                    'client_id' => 'viva-demo-client-id',
                    'client_secret' => 'viva-demo-client-secret',
                    'is_demo' => true,
                ],
            ]
        );

        BankingConnection::updateOrCreate(
            ['provider' => 'mypos', 'workspace_id' => $workspace->id],
            [
                'label' => 'MyPOS Store Account',
                'is_active' => true,
                'credentials' => [
                    'client_id' => 'client_95b0d079f395435aaf34dda1d9738b37',
                    'client_secret' => 'secret_66e8622ab22cbff78bc3dc519f26c53b4216ed7839dd3f034b1692addf3dc8d6',
                    'merchant_client_id' => 'cli_xz1nFRE9HRbb3u4fcbwUTl5t5Ogd',
                    'merchant_client_secret' => 'sec_x3iPoYX94LtxXe88RN46GT192rVHZUcvbZ5qgbdqLD1bOa5o1LlziDUnfAkd',
                    'partner_id' => 'mps-p-demo',
                    'application_id' => 'mps-app-demo',
                    'is_demo' => true,
                ],
            ]
        );
    }
}
