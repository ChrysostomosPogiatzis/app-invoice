<?php

namespace Database\Seeders;

use App\Models\BankingConnection;
use App\Models\BankTransaction;
use App\Models\CallLog;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Quote;
use App\Models\Reminder;
use App\Models\StaffLeaveRequest;
use App\Models\StaffMember;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceFeature;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. WORKSPACE ────────────────────────────────────────────────────────
        $workspace = Workspace::updateOrCreate(
            ['company_name' => 'Elite Rental ERP'],
            [
                'vat_number'  => 'CY98765432',
                'currency'    => 'EUR',
                'address'     => '48 Makarios Avenue, Limassol, Cyprus',
                'phone'       => '+357 25 123456',
                'email'       => 'accounts@eliterental.cy',
            ]
        );

        // ─── 2. USERS ─────────────────────────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@erp.com'],
            [
                'name'           => 'John Panteli',
                'password'       => Hash::make('password'),
                'is_super_admin' => true,
            ]
        );
        $admin->workspaces()->sync([$workspace->id => ['role' => 'owner']]);
        $admin->update(['current_workspace_id' => $workspace->id]);

        $manager = User::updateOrCreate(
            ['email' => 'maria@erp.com'],
            [
                'name'           => 'Maria Christodoulou',
                'password'       => Hash::make('password'),
                'is_super_admin' => false,
            ]
        );
        $manager->workspaces()->sync([$workspace->id => ['role' => 'admin']]);
        $manager->update(['current_workspace_id' => $workspace->id]);

        // ─── 3. FEATURES ──────────────────────────────────────────────────────────
        foreach ([
            'crm_basic'         => true,
            'inventory_basic'   => true,
            'billing_standard'  => true,
            'call_intelligence' => true,
            'crm_reminders'     => true,
            'expense_manager'   => true,
            'asset_health'      => true,
            'warehouse_workflow'=> false,
        ] as $name => $enabled) {
            WorkspaceFeature::updateOrCreate(
                ['workspace_id' => $workspace->id, 'feature_name' => $name],
                ['is_enabled' => $enabled]
            );
        }

        // ─── 4. STAFF ─────────────────────────────────────────────────────────────
        $staffData = [
            ['name' => 'Andreas Georgiou',   'position' => 'Senior Technician',  'base_salary' => 2500.00, 'joining_date' => '2023-02-01', 'id_number' => 'CY123456', 'si_number' => 'SI001', 'annual_leave_total' => 21],
            ['name' => 'Eleni Papadopoulou', 'position' => 'Customer Support',   'base_salary' => 1400.00, 'joining_date' => '2023-06-15', 'id_number' => 'CY234567', 'si_number' => 'SI002', 'annual_leave_total' => 21],
            ['name' => 'Nikos Stavrou',      'position' => 'Warehouse Manager',  'base_salary' => 2100.00, 'joining_date' => '2022-11-01', 'id_number' => 'CY345678', 'si_number' => 'SI003', 'annual_leave_total' => 21],
            ['name' => 'Sofia Ioannou',      'position' => 'Accounts Executive', 'base_salary' => 1800.00, 'joining_date' => '2024-01-10', 'id_number' => 'CY456789', 'si_number' => 'SI004', 'annual_leave_total' => 21],
        ];

        $staff = [];
        foreach ($staffData as $s) {
            $staff[] = StaffMember::updateOrCreate(
                ['name' => $s['name'], 'workspace_id' => $workspace->id],
                $s
            );
        }

        // Staff leave requests
        $leaveRecords = [
            ['staff' => $staff[0], 'start' => now()->addDays(3),  'end' => now()->addDays(7),  'type' => 'annual',  'status' => 'approved'],
            ['staff' => $staff[1], 'start' => now()->addDays(14), 'end' => now()->addDays(14), 'type' => 'sick',    'status' => 'approved'],
            ['staff' => $staff[2], 'start' => now()->subDays(5),  'end' => now()->subDays(3),  'type' => 'annual',  'status' => 'approved'],
            ['staff' => $staff[3], 'start' => now()->addDays(21), 'end' => now()->addDays(25), 'type' => 'annual',  'status' => 'approved'],
        ];

        foreach ($leaveRecords as $lr) {
            $start = Carbon::instance($lr['start'])->startOfDay();
            $end   = Carbon::instance($lr['end'])->startOfDay();
            StaffLeaveRequest::updateOrCreate(
                ['staff_member_id' => $lr['staff']->id, 'start_date' => $start->toDateString()],
                [
                    'end_date'   => $end->toDateString(),
                    'days_count' => $start->diffInWeekdays($end) + 1,
                    'type'       => $lr['type'],
                    'status'     => $lr['status'],
                    'reason'     => 'Demo seeded leave request',
                ]
            );
        }

        // ─── 5. CONTACTS ─────────────────────────────────────────────────────────
        $contactsData = [
            ['name' => 'Royal Plaza Hotel',       'company_name' => 'Royal Plaza Hotels Ltd',   'email' => 'booking@royalplaza.cy',     'mobile_number' => '+357 25 555001'],
            ['name' => 'City Conference Center',  'company_name' => 'CCC Cyprus Ltd',            'email' => 'info@ccc.com.cy',          'mobile_number' => '+357 25 555002'],
            ['name' => 'Paphos Weddings Co.',     'company_name' => 'Paphos Events Ltd',         'email' => 'events@paphosweddings.cy', 'mobile_number' => '+357 26 555003'],
            ['name' => 'Arena Live Entertainment','company_name' => 'Arena Group',               'email' => 'ops@arenagroup.cy',        'mobile_number' => '+357 22 555004'],
            ['name' => 'Sunset Beach Resort',     'company_name' => 'Sunset Hospitality',        'email' => 'mgmt@sunsetbeach.cy',      'mobile_number' => '+357 23 555005'],
            ['name' => 'Stavros Petrides',        'company_name' => null,                        'email' => 'stavros@petrides.cy',      'mobile_number' => '+357 99 100001'],
        ];

        $contacts = [];
        foreach ($contactsData as $c) {
            $contacts[] = Contact::updateOrCreate(
                ['email' => $c['email'], 'workspace_id' => $workspace->id],
                array_merge($c, ['workspace_id' => $workspace->id])
            );
        }

        // ─── 6. PRODUCTS ─────────────────────────────────────────────────────────
        $productsData = [
            ['name' => 'Power Generator 250kVA',    'sku' => 'PWR-GEN-250',  'unit_price_gross' => 535.50,  'purchase_price' => 15000.00, 'current_stock' => 2,    'product_type' => 'physical'],
            ['name' => 'Power Generator 100kVA',    'sku' => 'PWR-GEN-100',  'unit_price_gross' => 285.00,  'purchase_price' => 8000.00,  'current_stock' => 3,    'product_type' => 'physical'],
            ['name' => 'Outdoor Stage Mesh 10x8m',  'sku' => 'STG-MSH-10',   'unit_price_gross' => 2618.00, 'purchase_price' => 24000.00, 'current_stock' => 1,    'product_type' => 'physical'],
            ['name' => 'LED DJ Light Set (12pc)',    'sku' => 'LGT-LED-DJ12', 'unit_price_gross' => 890.00,  'purchase_price' => 3500.00,  'current_stock' => 4,    'product_type' => 'physical'],
            ['name' => 'Standard Black Chair',       'sku' => 'CHR-STD-BLK',  'unit_price_gross' => 2.98,    'purchase_price' => 15.00,    'current_stock' => 500,  'product_type' => 'physical'],
            ['name' => 'Round Banquet Table',        'sku' => 'TBL-BNQ-RND',  'unit_price_gross' => 15.00,   'purchase_price' => 85.00,    'current_stock' => 120,  'product_type' => 'physical'],
            ['name' => 'PA Sound System 5kW',        'sku' => 'AUD-PA-5K',    'unit_price_gross' => 480.00,  'purchase_price' => 9000.00,  'current_stock' => 2,    'product_type' => 'physical'],
            ['name' => 'Delivery & Setup Service',   'sku' => 'SVC-DLVY',     'unit_price_gross' => 120.00,  'purchase_price' => 0.00,     'current_stock' => 9999, 'product_type' => 'service'],
            ['name' => 'Technician On-Site (8h)',    'sku' => 'SVC-TECH-8H',  'unit_price_gross' => 250.00,  'purchase_price' => 0.00,     'current_stock' => 9999, 'product_type' => 'service'],
            // Low stock items to trigger alerts
            ['name' => 'Crowd Control Barrier 2m',  'sku' => 'SAF-BAR-2M',   'unit_price_gross' => 12.00,   'purchase_price' => 60.00,    'current_stock' => 5,    'product_type' => 'physical'],
            ['name' => 'Wireless Handheld Mic',      'sku' => 'AUD-MIC-WH',   'unit_price_gross' => 85.00,   'purchase_price' => 320.00,   'current_stock' => 3,    'product_type' => 'physical'],
        ];

        $products = [];
        foreach ($productsData as $p) {
            $products[] = Product::updateOrCreate(
                ['sku' => $p['sku'], 'workspace_id' => $workspace->id],
                array_merge($p, ['workspace_id' => $workspace->id, 'vat_rate' => 19.00])
            );
        }

        // ─── 7. INVOICES + PAYMENTS ───────────────────────────────────────────────
        $invoicesData = [
            // Paid invoices (historical)
            ['num' => 'INV-2026-001', 'contact' => 0, 'days_ago' => 60, 'due_offset' => 30, 'net' => 4200.00,  'vat' => 798.00,  'gross' => 4998.00,  'paid' => 4998.00,  'status' => 'paid'],
            ['num' => 'INV-2026-002', 'contact' => 1, 'days_ago' => 45, 'due_offset' => 30, 'net' => 1250.00,  'vat' => 237.50,  'gross' => 1487.50,  'paid' => 1487.50,  'status' => 'paid'],
            ['num' => 'INV-2026-003', 'contact' => 2, 'days_ago' => 30, 'due_offset' => 30, 'net' => 7800.00,  'vat' => 1482.00, 'gross' => 9282.00,  'paid' => 9282.00,  'status' => 'paid'],
            ['num' => 'INV-2026-004', 'contact' => 3, 'days_ago' => 20, 'due_offset' => 14, 'net' => 3100.00,  'vat' => 589.00,  'gross' => 3689.00,  'paid' => 3689.00,  'status' => 'paid'],
            // Partially paid
            ['num' => 'INV-2026-005', 'contact' => 4, 'days_ago' => 15, 'due_offset' => 30, 'net' => 5500.00,  'vat' => 1045.00, 'gross' => 6545.00,  'paid' => 3000.00,  'status' => 'partial'],
            // Unpaid, upcoming
            ['num' => 'INV-2026-006', 'contact' => 0, 'days_ago' => 10, 'due_offset' => 20, 'net' => 2200.00,  'vat' => 418.00,  'gross' => 2618.00,  'paid' => 0.00,     'status' => 'unpaid'],
            ['num' => 'INV-2026-007', 'contact' => 2, 'days_ago' => 5,  'due_offset' => 14, 'net' => 1400.00,  'vat' => 266.00,  'gross' => 1666.00,  'paid' => 0.00,     'status' => 'unpaid'],
            // Overdue
            ['num' => 'INV-2026-008', 'contact' => 5, 'days_ago' => 50, 'due_offset' => -20, 'net' => 850.00,   'vat' => 161.50,  'gross' => 1011.50,  'paid' => 0.00,     'status' => 'unpaid'],
            ['num' => 'INV-2026-009', 'contact' => 1, 'days_ago' => 40, 'due_offset' => -5,  'net' => 1900.00,  'vat' => 361.00,  'gross' => 2261.00,  'paid' => 0.00,     'status' => 'unpaid'],
            // Draft / this month
            ['num' => 'INV-2026-010', 'contact' => 3, 'days_ago' => 2,  'due_offset' => 30, 'net' => 3800.00,  'vat' => 722.00,  'gross' => 4522.00,  'paid' => 0.00,     'status' => 'unpaid'],
        ];

        $invoices = [];
        foreach ($invoicesData as $inv) {
            $date    = now()->subDays($inv['days_ago']);
            $dueDate = $date->copy()->addDays($inv['due_offset']);
            $balance = $inv['gross'] - $inv['paid'];

            $invoice = Invoice::updateOrCreate(
                ['invoice_number' => $inv['num'], 'workspace_id' => $workspace->id],
                [
                    'contact_id'       => $contacts[$inv['contact']]->id,
                    'date'             => $date->toDateString(),
                    'due_date'         => $dueDate->toDateString(),
                    'subtotal_net'     => $inv['net'],
                    'total_vat_amount' => $inv['vat'],
                    'discount'         => 0.00,
                    'grand_total_gross'=> $inv['gross'],
                    'amount_paid'      => $inv['paid'],
                    'balance_due'      => $balance,
                    'status'           => $inv['status'],
                    'doc_type'         => 'invoice',
                ]
            );

            if ($invoice->items()->count() === 0) {
                $invoice->items()->create([
                    'description'    => 'Equipment rental & services — ' . $inv['num'],
                    'quantity'       => 1,
                    'unit_price_net' => $inv['net'],
                    'vat_rate'       => 19.00,
                    'total_gross'    => $inv['gross'],
                ]);
            }

            // Create payment if paid / partial
            if ($inv['paid'] > 0) {
                Payment::updateOrCreate(
                    ['invoice_id' => $invoice->id, 'amount' => $inv['paid']],
                    [
                        'payment_method' => collect(['bank_transfer', 'card', 'cash'])->random(),
                        'payment_date'   => $date->copy()->addDays(rand(1, min(10, max(1, $inv['due_offset']))))->toDateString(),
                        'reference'      => 'PAY-' . strtoupper(substr(md5($inv['num']), 0, 6)),
                    ]
                );
            }

            $invoices[] = $invoice;
        }

        // ─── 8. QUOTES ────────────────────────────────────────────────────────────
        $quotesData = [
            ['num' => 'QT-2026-001', 'contact' => 1, 'days_ago' => 8,  'valid_days' => 30, 'net' => 6400.00, 'vat' => 1216.00, 'gross' => 7616.00, 'status' => 'sent'],
            ['num' => 'QT-2026-002', 'contact' => 2, 'days_ago' => 3,  'valid_days' => 14, 'net' => 1800.00, 'vat' => 342.00,  'gross' => 2142.00, 'status' => 'draft'],
            ['num' => 'QT-2026-003', 'contact' => 4, 'days_ago' => 12, 'valid_days' => 7,  'net' => 9200.00, 'vat' => 1748.00, 'gross' => 10948.00,'status' => 'viewed'],
            ['num' => 'QT-2026-004', 'contact' => 0, 'days_ago' => 20, 'valid_days' => 30, 'net' => 3300.00, 'vat' => 627.00,  'gross' => 3927.00, 'status' => 'accepted'],
            ['num' => 'QT-2026-005', 'contact' => 5, 'days_ago' => 5,  'valid_days' => 21, 'net' => 520.00,  'vat' => 98.80,   'gross' => 618.80,  'status' => 'draft'],
        ];

        foreach ($quotesData as $q) {
            $date      = now()->subDays($q['days_ago']);
            $validUntil= $date->copy()->addDays($q['valid_days']);
            $quote = Quote::updateOrCreate(
                ['quote_number' => $q['num'], 'workspace_id' => $workspace->id],
                [
                    'contact_id'        => $contacts[$q['contact']]->id,
                    'date'              => $date->toDateString(),
                    'valid_until'       => $validUntil->toDateString(),
                    'subtotal_net'      => $q['net'],
                    'total_vat_amount'  => $q['vat'],
                    'discount'          => 0.00,
                    'grand_total_gross' => $q['gross'],
                    'status'            => $q['status'],
                    'notes'             => 'Demo quote — awaiting client confirmation.',
                ]
            );
            if ($quote->items()->count() === 0) {
                $quote->items()->create([
                    'description'    => 'Full event equipment package — ' . $q['num'],
                    'quantity'       => 1,
                    'unit_price_net' => $q['net'],
                    'vat_rate'       => 19.00,
                    'total_gross'    => $q['gross'],
                ]);
            }
        }

        // ─── 9. EXPENSES ─────────────────────────────────────────────────────────
        $expensesData = [
            ['vendor' => 'Fuel & Logistics CY',       'category' => 'fuel',       'amount' => 380.00, 'days_ago' => 55],
            ['vendor' => 'Fibre Link Pay',             'category' => 'other',      'amount' => 120.00, 'days_ago' => 40],
            ['vendor' => 'CY Telecom Business',        'category' => 'utilities',  'amount' => 89.00,  'days_ago' => 30],
            ['vendor' => 'Warehouse Lease - April',    'category' => 'rent',       'amount' => 1800.00,'days_ago' => 28],
            ['vendor' => 'Insurance Premium Q2',       'category' => 'other',      'amount' => 540.00, 'days_ago' => 21],
            ['vendor' => 'Equipment Repair - Gen 250', 'category' => 'maintenance','amount' => 220.00, 'days_ago' => 14],
            ['vendor' => 'Office Supplies — April',   'category' => 'other',      'amount' => 67.50,  'days_ago' => 10],
            ['vendor' => 'Fuel & Logistics CY',       'category' => 'fuel',       'amount' => 310.00, 'days_ago' => 3],
        ];

        foreach ($expensesData as $e) {
            Expense::updateOrCreate(
                ['vendor_name' => $e['vendor'], 'workspace_id' => $workspace->id, 'amount' => $e['amount']],
                [
                    'category'     => $e['category'],
                    'expense_date' => now()->subDays($e['days_ago'])->toDateString(),
                    'workspace_id' => $workspace->id,
                ]
            );
        }

        // Payroll expenses for this month's staff
        foreach ($staff as $member) {
            Expense::updateOrCreate(
                ['vendor_name' => $member->name . ' — March Payroll', 'workspace_id' => $workspace->id],
                [
                    'category'     => 'payroll',
                    'amount'       => $member->base_salary,
                    'expense_date' => now()->subDays(rand(28, 35))->toDateString(),
                    'workspace_id' => $workspace->id,
                    'is_payroll'   => true,
                    'vendor_name'  => $member->name . ' — March Payroll',
                ]
            );
        }

        // ─── 10. REMINDERS ────────────────────────────────────────────────────────
        $remindersData = [
            ['contact' => 0, 'title' => 'Follow up on INV-2026-006 payment',    'days' => 2],
            ['contact' => 1, 'title' => 'Send revised quote for August event',   'days' => 4],
            ['contact' => 2, 'title' => 'Site visit — Paphos venue walkthrough', 'days' => 7],
            ['contact' => 4, 'title' => 'Confirm equipment list for resort',     'days' => 1],
            ['contact' => 5, 'title' => 'Chase overdue payment INV-2026-008',    'days' => -1], // overdue reminder
        ];

        foreach ($remindersData as $r) {
            Reminder::updateOrCreate(
                ['contact_id' => $contacts[$r['contact']]->id, 'title' => $r['title']],
                [
                    'remind_at' => now()->addDays($r['days'])->setTime(9, 0)->toDateTimeString(),
                ]
            );
        }

        // ─── 11. CALL LOGS ────────────────────────────────────────────────────────
        $callTypeMix = ['inbound', 'outbound', 'missed'];
        $callLogs = [
            ['contact' => 0, 'type' => 'inbound',  'duration' => 342, 'days_ago' => 1,  'note' => 'Discussed delivery schedule for June event.'],
            ['contact' => 1, 'type' => 'outbound', 'duration' => 180, 'days_ago' => 2,  'note' => 'Followed up on quote QT-2026-001.'],
            ['contact' => 2, 'type' => 'inbound',  'duration' => 520, 'days_ago' => 3,  'note' => 'Wedding setup requirements clarified.'],
            ['contact' => 3, 'type' => 'outbound', 'duration' => 90,  'days_ago' => 4,  'note' => 'Left message regarding payment.'],
            ['contact' => 4, 'type' => 'missed',   'duration' => 0,   'days_ago' => 5,  'note' => null],
            ['contact' => 5, 'type' => 'outbound', 'duration' => 240, 'days_ago' => 6,  'note' => 'Requested payment for overdue invoice.'],
            ['contact' => 0, 'type' => 'outbound', 'duration' => 310, 'days_ago' => 8,  'note' => 'Confirmed extra chairs for corporate dinner.'],
            ['contact' => 1, 'type' => 'inbound',  'duration' => 450, 'days_ago' => 10, 'note' => 'Requested additional sound system.'],
            ['contact' => 3, 'type' => 'missed',   'duration' => 0,   'days_ago' => 12, 'note' => null],
            ['contact' => 2, 'type' => 'outbound', 'duration' => 200, 'days_ago' => 15, 'note' => 'Sent venue list and generator options.'],
        ];

        foreach ($callLogs as $cl) {
            CallLog::updateOrCreate(
                [
                    'contact_id' => $contacts[$cl['contact']]->id,
                    'call_date'  => now()->subDays($cl['days_ago'])->setTime(rand(9, 17), rand(0, 59))->toDateTimeString(),
                ],
                [
                    'call_type'             => $cl['type'],
                    'call_duration_seconds' => $cl['duration'],
                    'call_notes'            => $cl['note'],
                ]
            );
        }

        // ─── 12. BANKING ─────────────────────────────────────────────────────────
        $viva = BankingConnection::updateOrCreate(
            ['provider' => 'vivawallet', 'workspace_id' => $workspace->id],
            [
                'label'       => 'Viva Wallet Business Account',
                'is_active'   => true,
                'credentials' => ['client_id' => 'viva-demo', 'client_secret' => 'demo-secret', 'is_demo' => true],
            ]
        );

        $mypos = BankingConnection::updateOrCreate(
            ['provider' => 'mypos', 'workspace_id' => $workspace->id],
            [
                'label'       => 'MyPOS Store Terminal',
                'is_active'   => true,
                'credentials' => ['client_id' => 'mps-demo', 'is_demo' => true],
            ]
        );

        $bankTxns = [
            ['ext' => 'TRA-001', 'conn' => $viva,  'provider' => 'vivawallet', 'days_ago' => 3,  'amount' => 4998.00,  'desc' => 'Payment — INV-2026-001'],
            ['ext' => 'TRA-002', 'conn' => $viva,  'provider' => 'vivawallet', 'days_ago' => 5,  'amount' => 1487.50,  'desc' => 'Payment — INV-2026-002'],
            ['ext' => 'TRA-003', 'conn' => $mypos, 'provider' => 'mypos',      'days_ago' => 7,  'amount' => 9282.00,  'desc' => 'Payment — INV-2026-003'],
            ['ext' => 'TRA-004', 'conn' => $viva,  'provider' => 'vivawallet', 'days_ago' => 10, 'amount' => 3689.00,  'desc' => 'Payment — INV-2026-004'],
            ['ext' => 'TRA-005', 'conn' => $mypos, 'provider' => 'mypos',      'days_ago' => 12, 'amount' => 3000.00,  'desc' => 'Partial — INV-2026-005'],
            ['ext' => 'TRA-006', 'conn' => $viva,  'provider' => 'vivawallet', 'days_ago' => 2,  'amount' => -1800.00, 'desc' => 'Warehouse Lease April'],
            ['ext' => 'TRA-007', 'conn' => $mypos, 'provider' => 'mypos',      'days_ago' => 4,  'amount' => -380.00,  'desc' => 'Fuel & Logistics CY'],
            ['ext' => 'TRA-008', 'conn' => $viva,  'provider' => 'vivawallet', 'days_ago' => 1,  'amount' => 750.00,   'desc' => 'Unmatched Income — reconcile'],
        ];

        foreach ($bankTxns as $tx) {
            BankTransaction::updateOrCreate(
                ['external_id' => $tx['ext'], 'workspace_id' => $workspace->id],
                [
                    'banking_connection_id' => $tx['conn']->id,
                    'provider'              => $tx['provider'],
                    'transaction_date'      => now()->subDays($tx['days_ago'])->toDateString(),
                    'amount'                => $tx['amount'],
                    'currency'              => 'EUR',
                    'status'                => 'completed',
                    'description'           => $tx['desc'],
                ]
            );
        }

        $this->command->info('✅ DemoSeeder complete — workspace, 4 staff, 6 contacts, 11 products, 10 invoices, 5 quotes, 12 expenses, 5 reminders, 10 call logs, 8 bank transactions.');
    }
}
