<?php

namespace Tests\Feature;

use App\Models\CallLog;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PublicShare;
use App\Models\Quote;
use App\Models\Reminder;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AppControllersTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_controller_renders_workspace_data(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();
        $contact = $this->createContact($workspace);
        $product = $this->createProduct($workspace, ['current_stock' => 3]);
        $invoice = $this->createInvoice($workspace, $contact, ['status' => 'unpaid', 'balance_due' => 150]);
        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => 50,
            'payment_method' => 'bank_transfer',
            'payment_date' => now(),
            'reference' => 'PAY-1',
        ]);
        Quote::create([
            'workspace_id' => $workspace->id,
            'contact_id' => $contact->id,
            'quote_number' => 'QUO-1001',
            'date' => now()->toDateString(),
            'valid_until' => now()->addDays(5)->toDateString(),
            'status' => 'draft',
            'discount' => 0,
            'subtotal_net' => 100,
            'total_vat_amount' => 19,
            'grand_total_gross' => 119,
        ]);
        Reminder::create([
            'contact_id' => $contact->id,
            'title' => 'Follow up',
            'remind_at' => now()->addDays(2),
        ]);
        CallLog::create([
            'contact_id' => $contact->id,
            'call_type' => 'outbound',
            'call_duration_seconds' => 120,
            'call_notes' => 'Checked status',
            'call_date' => now(),
        ]);
        Expense::create([
            'workspace_id' => $workspace->id,
            'category' => 'fuel',
            'amount' => 45,
            'expense_date' => now()->toDateString(),
            'vendor_name' => 'Shell',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard', ['period' => 'ytd']));

        $response->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Dashboard')
            ->where('period.key', 'ytd')
            ->where('stats.activeContacts', 1)
            ->where('stats.lowStockItems', 1)
            ->where('recentInvoices.0.invoice_number', $invoice->invoice_number)
        );
    }

    public function test_contact_controller_crud_flow(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();

        $create = $this->actingAs($user)->post(route('contacts.store'), [
            'name' => 'Alice Contact',
            'company_name' => 'Acme Events',
            'email' => 'alice@example.com',
            'mobile_number' => '+35799123456',
            'vat_number' => 'CY123',
            'address' => '1 Demo Street',
            'contact_type' => 'customer',
            'general_info' => 'VIP client',
        ]);

        $create->assertRedirect(route('contacts.index'));

        $contact = Contact::where('workspace_id', $workspace->id)->where('email', 'alice@example.com')->firstOrFail();

        $this->actingAs($user)->get(route('contacts.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Contacts/Index')
                ->where('contacts.data.0.email', 'alice@example.com')
            );

        $this->actingAs($user)->get(route('contacts.show', $contact))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Contacts/Show')
                ->where('contact.id', $contact->id)
            );

        $update = $this->actingAs($user)->put(route('contacts.update', $contact), [
            'name' => 'Alice Updated',
            'company_name' => 'Acme Events',
            'email' => 'alice@example.com',
            'mobile_number' => '+35799123456',
            'vat_number' => 'CY123',
            'address' => '2 Demo Street',
            'contact_type' => 'lead',
            'general_info' => 'Updated note',
        ]);

        $update->assertRedirect(route('contacts.index'));
        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Alice Updated',
            'contact_type' => 'lead',
        ]);

        $delete = $this->actingAs($user)->delete(route('contacts.destroy', $contact));
        $delete->assertRedirect(route('contacts.index'));
        $this->assertSoftDeleted('contacts', ['id' => $contact->id]);
    }

    public function test_contact_controller_uses_current_workspace_not_first_membership(): void
    {
        [$workspaceA] = $this->createWorkspaceUser();
        [$workspaceB, $user] = $this->createWorkspaceUser('owner');

        $workspaceA->users()->attach($user->id, ['role' => 'staff']);
        $user->update(['current_workspace_id' => $workspaceB->id]);

        $contactInA = $this->createContact($workspaceA, ['email' => 'a@example.com']);
        $contactInB = $this->createContact($workspaceB, ['email' => 'b@example.com']);

        $this->actingAs($user)->get(route('contacts.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Contacts/Index')
                ->where('contacts.data.0.id', $contactInB->id)
                ->where('contacts.data.0.email', 'b@example.com')
                ->missing('contacts.data.1')
            );

        $this->actingAs($user)->get(route('contacts.show', $contactInA))
            ->assertNotFound();
    }

    public function test_product_controller_flow_including_stock_adjustments(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();
        $category = $this->createCategory($workspace);

        $create = $this->actingAs($user)->post(route('products.store'), [
            'name' => 'Speaker',
            'sku' => 'SPK-1',
            'product_type' => 'physical',
            'product_category_id' => $category->id,
            'unit_price_gross' => 119,
            'vat_rate' => 19,
            'current_stock' => 5,
            'purchase_price' => 80,
            'acquisition_date' => now()->toDateString(),
        ]);
        $create->assertRedirect(route('products.index'));

        $product = Product::where('workspace_id', $workspace->id)->where('sku', 'SPK-1')->firstOrFail();

        $this->actingAs($user)->get(route('products.show', $product))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Products/Show')
                ->where('product.id', $product->id)
            );

        $this->actingAs($user)->put(route('products.update', $product), [
            'name' => 'Speaker Pro',
            'sku' => 'SPK-1',
            'product_type' => 'physical',
            'product_category_id' => $category->id,
            'unit_price_gross' => 129,
            'vat_rate' => 19,
            'current_stock' => 6,
            'purchase_price' => 82,
            'acquisition_date' => now()->toDateString(),
        ])->assertRedirect(route('products.index'));

        $this->actingAs($user)->patch(route('products.update-partial', $product), [
            'sku' => 'SPK-2',
            'name' => 'Speaker Prime',
        ])->assertRedirect();

        $this->actingAs($user)->post(route('products.adjust-stock', $product), [
            'quantity' => 2,
            'direction' => 'in',
            'movement_type' => 'purchase',
            'notes' => 'Restocked',
        ])->assertRedirect();

        $product->refresh();
        $this->assertSame('SPK-2', $product->sku);
        $this->assertEquals(8.0, (float) $product->current_stock);
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'direction' => 'in',
        ]);

        $this->actingAs($user)->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));
    }

    public function test_product_category_controller_creates_category_for_workspace(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();

        $response = $this->actingAs($user)->postJson(route('product-categories.store'), [
            'name' => 'Lighting',
        ]);

        $response->assertOk()->assertJsonFragment([
            'workspace_id' => $workspace->id,
            'name' => 'Lighting',
        ]);
    }

    public function test_invoice_controller_store_show_download_and_destroy(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();
        $contact = $this->createContact($workspace);
        $product = $this->createProduct($workspace, ['current_stock' => 10, 'unit_price_gross' => 119, 'vat_rate' => 19]);

        Storage::fake('public');

        $response = $this->actingAs($user)->post(route('invoices.store'), [
            'contact_id' => $contact->id,
            'date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'discount' => 0,
            'items' => [
                [
                    'product_id' => $product->id,
                    'description' => 'Speaker rental',
                    'quantity' => 2,
                    'unit_price_net' => 100,
                    'vat_rate' => 19,
                ],
            ],
            'attachment_files' => [
                UploadedFile::fake()->create('note.pdf', 20, 'application/pdf'),
            ],
        ]);

        $invoice = Invoice::where('workspace_id', $workspace->id)->latest('id')->firstOrFail();

        $response->assertRedirect(route('invoices.show', $invoice));
        $this->assertDatabaseHas('invoice_items', ['invoice_id' => $invoice->id, 'description' => 'Speaker rental']);
        $this->assertDatabaseHas('invoice_vat_breakdown', ['invoice_id' => $invoice->id, 'vat_rate' => 19]);
        $this->assertDatabaseHas('attachments', ['related_id' => $invoice->id, 'related_type' => 'invoice']);

        $this->actingAs($user)->get(route('invoices.show', $invoice))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Finance/InvoiceShow')
                ->where('invoice.id', $invoice->id)
            );

        $this->actingAs($user)->get(route('invoices.download', $invoice))
            ->assertOk();

        $this->actingAs($user)->delete(route('invoices.destroy', $invoice))
            ->assertRedirect(route('invoices.index'));
    }

    public function test_payment_controller_records_payment_and_blocks_other_workspace_invoice(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();
        $contact = $this->createContact($workspace);
        $invoice = $this->createInvoice($workspace, $contact, [
            'grand_total_gross' => 200,
            'balance_due' => 200,
            'amount_paid' => 0,
            'status' => 'unpaid',
        ]);

        $this->actingAs($user)->post(route('payments.store'), [
            'invoice_id' => $invoice->id,
            'amount' => 75,
            'payment_method' => 'cash',
            'payment_date' => now()->toDateString(),
            'notes' => 'Deposit',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('partial', $invoice->status);
        $this->assertEquals(75.0, (float) $invoice->amount_paid);
        $this->assertEquals(125.0, (float) $invoice->balance_due);

        [$otherWorkspace] = $this->createWorkspaceUser();
        $otherContact = $this->createContact($otherWorkspace);
        $otherInvoice = $this->createInvoice($otherWorkspace, $otherContact);

        $this->actingAs($user)->post(route('payments.store'), [
            'invoice_id' => $otherInvoice->id,
            'amount' => 10,
            'payment_date' => now()->toDateString(),
        ])->assertNotFound();
    }

    public function test_quote_controller_full_flow(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();
        $contact = $this->createContact($workspace);
        $product = $this->createProduct($workspace, ['current_stock' => 5, 'unit_price_gross' => 119, 'vat_rate' => 19]);

        $create = $this->actingAs($user)->post(route('quotes.store'), [
            'contact_id' => $contact->id,
            'date' => now()->toDateString(),
            'valid_until' => now()->addDays(7)->toDateString(),
            'discount' => 0,
            'notes' => 'Quote note',
            'terms' => 'Net 14 days',
            'items' => [
                [
                    'product_id' => $product->id,
                    'description' => 'Quoted speaker',
                    'quantity' => 1,
                    'unit_price_net' => 100,
                    'vat_rate' => 19,
                ],
            ],
        ]);

        $quote = Quote::where('workspace_id', $workspace->id)->latest('id')->firstOrFail();
        $create->assertRedirect(route('quotes.show', $quote));

        $this->actingAs($user)->put(route('quotes.update', $quote), [
            'contact_id' => $contact->id,
            'date' => now()->toDateString(),
            'valid_until' => now()->addDays(10)->toDateString(),
            'discount' => 10,
            'notes' => 'Updated note',
            'terms' => 'Updated terms',
            'items' => [
                [
                    'product_id' => $product->id,
                    'description' => 'Updated quoted speaker',
                    'quantity' => 2,
                    'unit_price_net' => 100,
                    'vat_rate' => 19,
                ],
            ],
        ])->assertRedirect(route('quotes.show', $quote));

        $this->actingAs($user)->patch(route('quotes.update-status', $quote), [
            'status' => 'sent',
        ])->assertRedirect(route('quotes.show', $quote));

        $convert = $this->actingAs($user)->post(route('quotes.convert', $quote));
        $invoice = Invoice::where('workspace_id', $workspace->id)->latest('id')->firstOrFail();

        $convert->assertRedirect(route('invoices.show', $invoice));
        $quote->refresh();
        $this->assertSame('converted', $quote->status);
        $this->assertSame($invoice->id, $quote->converted_to_invoice_id);

        $this->actingAs($user)->delete(route('quotes.destroy', $quote))
            ->assertRedirect(route('quotes.index'));
    }

    public function test_expense_controller_crud_flow(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();
        Storage::fake('public');

        $create = $this->actingAs($user)->post(route('expenses.store'), [
            'category' => 'fuel',
            'amount' => 35.50,
            'expense_date' => now()->toDateString(),
            'reminder_time' => '09:15',
            'vendor_name' => 'Fuel Station',
            'receipt_file' => UploadedFile::fake()->image('receipt.jpg'),
        ]);

        $create->assertRedirect(route('expenses.index'));
        $expense = Expense::where('workspace_id', $workspace->id)->latest('id')->firstOrFail();

        $this->actingAs($user)->get(route('expenses.show', $expense))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Finance/ExpenseShow')
                ->where('expense.id', $expense->id)
            );

        $this->actingAs($user)->put(route('expenses.update', $expense), [
            'category' => 'utility',
            'amount' => 40,
            'expense_date' => now()->toDateString(),
            'reminder_time' => '10:00',
            'vendor_name' => 'Power Co',
        ])->assertRedirect(route('expenses.index'));

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'category' => 'utility',
            'vendor_name' => 'Power Co',
        ]);

        $this->actingAs($user)->delete(route('expenses.destroy', $expense))
            ->assertRedirect(route('expenses.index'));
    }

    public function test_communication_controller_index_and_mutations(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();
        $contact = $this->createContact($workspace);
        $invoice = $this->createInvoice($workspace, $contact);

        $store = $this->actingAs($user)->post(route('communications.store'), [
            'contact_id' => $contact->id,
            'invoice_id' => $invoice->id,
            'call_type' => 'outbound',
            'call_duration_seconds' => 180,
            'call_notes' => 'Discussed quote',
        ]);

        $store->assertRedirect();
        $log = CallLog::latest('id')->firstOrFail();

        $this->actingAs($user)->get(route('communications.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Communications/Index')
                ->where('logs.data.0.id', $log->id)
            );

        $this->actingAs($user)->put(route('communications.update', $log), [
            'invoice_id' => null,
            'call_type' => 'missed',
            'call_duration_seconds' => 0,
            'call_notes' => 'No answer',
        ])->assertRedirect();

        $this->assertDatabaseHas('call_logs', [
            'id' => $log->id,
            'call_type' => 'missed',
            'call_notes' => 'No answer',
        ]);

        $this->actingAs($user)->delete(route('communications.destroy', $log))
            ->assertRedirect();
    }

    public function test_reminder_controller_index_store_and_destroy(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();
        $contact = $this->createContact($workspace);

        $store = $this->actingAs($user)->post(route('reminders.store'), [
            'contact_id' => $contact->id,
            'title' => 'Call back',
            'remind_at' => now()->addDay()->toDateTimeString(),
        ]);

        $store->assertRedirect();
        $reminder = Reminder::latest('id')->firstOrFail();

        $this->actingAs($user)->get(route('reminders.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Reminders/Index')
                ->where('reminders.0.id', $reminder->id)
            );

        $this->actingAs($user)->delete(route('reminders.destroy', $reminder->id))
            ->assertRedirect();
    }

    public function test_report_controller_index_and_exports(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();
        $contact = $this->createContact($workspace);
        $this->createInvoice($workspace, $contact, ['grand_total_gross' => 300, 'balance_due' => 300]);
        $this->createProduct($workspace, ['sku' => 'INV-1', 'purchase_price' => 50, 'current_stock' => 2]);
        Expense::create([
            'workspace_id' => $workspace->id,
            'category' => 'marketing',
            'amount' => 45,
            'expense_date' => now()->toDateString(),
            'vendor_name' => 'Ads',
        ]);

        $this->actingAs($user)->get(route('reports.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Reports/Index')
                ->where('overview.totalBilled', 300)
            );

        $this->actingAs($user)->get(route('reports.export', ['type' => 'financial']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->actingAs($user)->get(route('reports.export', ['type' => 'inventory']))
            ->assertOk();

        $this->actingAs($user)->get(route('reports.export', ['type' => 'clients']))
            ->assertOk();
    }

    public function test_settings_controller_edit_and_update(): void
    {
        [$workspace, $user] = $this->createWorkspaceUser();

        $this->actingAs($user)->get(route('settings.edit'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Settings/Edit')
                ->where('workspace.id', $workspace->id)
            );

        $response = $this->actingAs($user)->put(route('settings.update'), [
            'company_name' => 'Updated Workspace',
            'vat_number' => 'CY555',
            'tic_number' => 'TIC-9',
            'address' => 'Updated address',
            'phone' => '+357123',
            'email' => 'office@example.com',
            'currency' => 'EUR',
            'iban' => 'CY123',
            'bic' => 'ABCDEFGH',
            'brand_color' => '#123456',
            'invoice_prefix' => 'INV-',
            'next_invoice_number' => 2024,
            'features' => [
                'maintenance' => true,
                'crm_reminders' => false,
            ],
        ]);

        $response->assertRedirect();
        $workspace->refresh();
        $this->assertSame('Updated Workspace', $workspace->company_name);
        $this->assertDatabaseHas('workspace_features', [
            'workspace_id' => $workspace->id,
            'feature_name' => 'maintenance',
            'is_enabled' => 1,
        ]);
    }

    public function test_public_invoice_controller_show_and_sign(): void
    {
        [$workspace] = $this->createWorkspaceUser();
        $contact = $this->createContact($workspace);
        $invoice = $this->createInvoice($workspace, $contact, ['status' => 'unpaid']);
        $share = PublicShare::create([
            'invoice_id' => $invoice->id,
            'share_token' => 'share-token-123',
            'view_count' => 0,
        ]);

        $this->get(route('public.invoice.show', $share->share_token))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Public/InvoiceView')
                ->where('invoice.id', $invoice->id)
            );

        $share->refresh();
        $this->assertSame(1, $share->view_count);

        $this->post(route('public.invoice.sign', $share->share_token), [
            'signature_base64' => 'data:image/png;base64,abc123',
            'customer_name' => 'Alice Customer',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame('unpaid', $invoice->status);
        $this->assertSame('Alice Customer', $invoice->customer_signature_name);
        $this->assertDatabaseHas('audit_logs', [
            'workspace_id' => $workspace->id,
            'entity_name' => 'Invoice',
            'entity_id' => $invoice->id,
            'action_type' => 'SIGN',
        ]);
    }

    private function createWorkspaceUser(string $role = 'owner', ?Workspace $workspace = null): array
    {
        $workspace ??= Workspace::create([
            'company_name' => 'Workspace Test Company',
            'currency' => 'EUR',
            'invoice_prefix' => 'INV-',
            'next_invoice_number' => 1001,
            'quote_prefix' => 'Q-',
            'next_quote_number' => 1001,
            'brand_color' => '#4F46E5',
        ]);

        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);

        $workspace->users()->attach($user->id, ['role' => $role]);

        return [$workspace, $user];
    }

    private function createContact(Workspace $workspace, array $attributes = []): Contact
    {
        return Contact::create(array_merge([
            'workspace_id' => $workspace->id,
            'name' => 'Demo Contact',
            'company_name' => 'Demo Company',
            'email' => 'contact' . Contact::count() . '@example.com',
            'mobile_number' => '+35799000000',
            'vat_number' => 'CYVAT' . Contact::count(),
            'address' => 'Address line',
            'contact_type' => 'customer',
            'general_info' => 'Demo contact',
        ], $attributes));
    }

    private function createCategory(Workspace $workspace, array $attributes = []): ProductCategory
    {
        return ProductCategory::create(array_merge([
            'workspace_id' => $workspace->id,
            'name' => 'Category ' . ProductCategory::count(),
        ], $attributes));
    }

    private function createProduct(Workspace $workspace, array $attributes = []): Product
    {
        return Product::create(array_merge([
            'workspace_id' => $workspace->id,
            'name' => 'Product ' . Product::count(),
            'sku' => 'SKU-' . (Product::count() + 1),
            'product_type' => 'physical',
            'product_category_id' => null,
            'unit_price_gross' => 100,
            'vat_rate' => 19,
            'current_stock' => 10,
            'purchase_price' => 60,
            'acquisition_date' => now()->toDateString(),
        ], $attributes));
    }

    private function createInvoice(Workspace $workspace, Contact $contact, array $attributes = []): Invoice
    {
        return Invoice::create(array_merge([
            'workspace_id' => $workspace->id,
            'contact_id' => $contact->id,
            'invoice_number' => 'INV-' . str_pad((string) (Invoice::count() + 1001), 4, '0', STR_PAD_LEFT),
            'doc_type' => 'invoice',
            'date' => now()->toDateString(),
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'unpaid',
            'currency' => 'EUR',
            'exchange_rate' => 1,
            'subtotal_net' => 100,
            'total_vat_amount' => 19,
            'grand_total_gross' => 119,
            'amount_paid' => 0,
            'balance_due' => 119,
        ], $attributes));
    }
}
