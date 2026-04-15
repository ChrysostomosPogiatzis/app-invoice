<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\CallLog;
use App\Models\Reminder;
use App\Models\Workspace;
use App\Models\StockMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdvancedCRMSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::first();
        if (!$workspace) return;

        // Ensure categories exist
        $catSound = ProductCategory::updateOrCreate(['name' => 'Sound & Audio'], ['workspace_id' => $workspace->id]);
        $catLight = ProductCategory::updateOrCreate(['name' => 'Lighting & FX'], ['workspace_id' => $workspace->id]);
        $catStage = ProductCategory::updateOrCreate(['name' => 'Stage & Truss'], ['workspace_id' => $workspace->id]);

        // Ensure products exist
        $products = [
            ['name' => 'Line Array Speaker XL', 'category_id' => $catSound->id, 'price' => 450, 'vat' => 19],
            ['name' => 'Moving Head Beam 7R', 'category_id' => $catLight->id, 'price' => 120, 'vat' => 19],
            ['name' => 'Stage Deck 2x1m', 'category_id' => $catStage->id, 'price' => 45, 'vat' => 19],
            ['name' => 'Wireless Mic System', 'category_id' => $catSound->id, 'price' => 85, 'vat' => 19],
            ['name' => 'LED Par 18x12W', 'category_id' => $catLight->id, 'price' => 35, 'vat' => 19],
        ];

        $spawnedProducts = [];
        foreach ($products as $p) {
            $spawnedProducts[] = Product::updateOrCreate(
                ['name' => $p['name'], 'workspace_id' => $workspace->id],
                [
                    'sku' => strtoupper(substr($p['name'], 0, 3)) . '-' . rand(100, 999),
                    'product_type' => 'rental',
                    'unit_price_gross' => $p['price'],
                    'vat_rate' => $p['vat'],
                    'current_stock' => rand(50, 200),
                    'product_category_id' => $p['category_id']
                ]
            );
        }

        $entities = [
            [
                'name' => 'Starlight Productions LLC',
                'company' => 'Starlight Global',
                'info' => 'High-priority enterprise account. Established in 2021. Focuses on large scale festivals. Prefers Martin lighting systems.',
                'interactions' => [
                    'Initial onboarding negotiation. Secured 3-year exclusivity on sound.',
                    'Follow-up regarding Summer Fest 2022. Client requested redundancy on all circuits.',
                    'Quarterly review. Satisfaction high. Planning for 2024 expansion.',
                    'Emergency callback: Logistics delay on site 4. Resolved within 2 hours.'
                ]
            ],
            [
                'name' => 'John Aris',
                'company' => 'Aris Weddings & Events',
                'info' => 'Boutique wedding planner. High volume, low single-event value. Very precise about cable management visibility.',
                'interactions' => [
                    'Introductory meeting. Impressed by white speaker covers.',
                    'Payment delay on INV-882. Resolved after follow up.',
                    'Inbound inquiry: 2025 Christmas gala bookings.',
                ]
            ],
            [
                'name' => 'Grand Hotel Amathus',
                'company' => 'Amathus Hospitality Group',
                'info' => 'Permanent installation and recurring corporate galas. Requires tech-on-site for every event.',
                'interactions' => [
                    'Contract renewal. 2-year maintenance and event support signed.',
                    'Semi-annual gear audit. All units functional.',
                    'Operational brief for the International Banking Summit.'
                ]
            ],
            [
                'name' => 'Elena Papa',
                'company' => 'Creative Agency Cyprus',
                'info' => 'Middle-man agency. Hard negotiators. Always requires VAT breakdown for their clients.',
                'interactions' => [
                    'Discussion on wholesale pricing models.',
                    'Call regarding brand colors on LED bars. Resolved.',
                    'Booking for 5-day product launch in Limassol.'
                ]
            ],
            [
                'name' => 'City Municipality of Nicosia',
                'company' => 'Nicosia Municipality',
                'info' => 'Governmental body. Payment cycles 60-90 days. High prestige but slow administration.',
                'interactions' => [
                    'Tender presentation for Christmas 2023.',
                    'Site visit: Eleftheria Square power requirements.',
                    'Post-event feedback: Sound dispersion was excellent.'
                ]
            ]
        ];

        foreach ($entities as $index => $e) {
            $contact = Contact::create([
                'workspace_id' => $workspace->id,
                'name' => $e['name'],
                'company_name' => $e['company'],
                'email' => strtolower(str_replace(' ', '.', $e['name'])) . '@example.com',
                'mobile_number' => '+357 99' . rand(100000, 999999),
                'general_info' => $e['info'],
                'contact_type' => 'b2b'
            ]);

            // Generate 5 years of invoices
            for ($year = 2021; $year <= 2025; $year++) {
                $numInvoices = rand(2, 5);
                for ($i = 0; $i < $numInvoices; $i++) {
                    $date = Carbon::create($year, rand(1, 12), rand(1, 28));
                    
                    $invoiceNumber = $workspace->invoice_prefix . $workspace->next_invoice_number;
                    $workspace->increment('next_invoice_number');

                    $invoice = Invoice::create([
                        'workspace_id' => $workspace->id,
                        'contact_id' => $contact->id,
                        'invoice_number' => $invoiceNumber,
                        'date' => $date,
                        'due_date' => $date->copy()->addDays(30),
                        'subtotal_net' => 0,
                        'total_vat_amount' => 0,
                        'grand_total_gross' => 0,
                        'status' => 'paid',
                        'doc_type' => 'invoice'
                    ]);

                    $subtotal = 0;
                    $vatItems = 0;
                    
                    $pick = array_rand($spawnedProducts, rand(1, 3));
                    if (!is_array($pick)) $pick = [$pick];

                    foreach ($pick as $prodIdx) {
                        $prod = $spawnedProducts[$prodIdx];
                        $qty = rand(1, 10);
                        $net = $qty * ($prod->unit_price_gross / (1 + ($prod->vat_rate / 100)));
                        $vat = $net * ($prod->vat_rate / 100);

                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'product_id' => $prod->id,
                            'description' => $prod->name,
                            'quantity' => $qty,
                            'unit_price_net' => $prod->unit_price_gross / (1 + ($prod->vat_rate / 100)),
                            'vat_rate' => $prod->vat_rate
                        ]);

                        StockMovement::create([
                            'product_id' => $prod->id,
                            'quantity' => $qty,
                            'direction' => 'out',
                            'movement_type' => 'sale',
                            'reference_id' => $invoice->id,
                            'notes' => "Historical seed: Invoice #{$invoiceNumber}"
                        ]);

                        $subtotal += $net;
                        $vatItems += $vat;
                    }

                    $invoice->update([
                        'subtotal_net' => $subtotal,
                        'total_vat_amount' => $vatItems,
                        'grand_total_gross' => $subtotal + $vatItems,
                        'amount_paid' => $subtotal + $vatItems,
                        'balance_due' => 0
                    ]);

                    // Generate VAT breakdown record
                    DB::table('invoice_vat_breakdown')->insert([
                        'invoice_id' => $invoice->id,
                        'vat_rate' => 19,
                        'net_amount' => $subtotal,
                        'vat_amount' => $vatItems,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }

            // Generate Communication Logs
            foreach ($e['interactions'] as $note) {
                CallLog::create([
                    'contact_id' => $contact->id,
                    'call_date' => Carbon::now()->subMonths(rand(1, 48)),
                    'call_type' => ['inbound', 'outbound'][rand(0, 1)],
                    'call_notes' => $note,
                    'call_duration_seconds' => rand(60, 900)
                ]);
            }

            // Generate active reminders
            Reminder::create([
                'contact_id' => $contact->id,
                'title' => 'Projected Quarterly Review',
                'remind_at' => Carbon::now()->addDays(rand(5, 60))
            ]);
        }
    }
}
