<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspaces', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('vat_number')->nullable();
            $table->string('tic_number')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('iban')->nullable();
            $table->string('bic')->nullable();
            $table->text('logo_url')->nullable();
            $table->string('brand_color', 7)->default('#2ecc71');
            $table->string('currency', 10)->default('EUR');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('workspace_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'admin', 'staff', 'viewer'])->default('owner');
            $table->timestamps();
            $table->unique(['user_id', 'workspace_id'], 'unique_user_workspace');
        });

        Schema::create('workspace_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('feature_name', 50);
            $table->boolean('is_enabled')->default(true);
            $table->unique(['workspace_id', 'feature_name'], 'workspace_feature_unique');
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('email')->nullable()->index();
            $table->string('mobile_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->text('general_info')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->timestamp('remind_at');
            $table->boolean('is_completed')->default(false);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('sku')->nullable();
            $table->enum('product_type', ['physical', 'service', 'rental'])->default('physical');
            $table->decimal('unit_price_net', 15, 2)->default(0.00);
            $table->decimal('vat_rate', 5, 2)->default(19.00);
            $table->decimal('current_stock', 15, 2)->default(0.00);
            $table->decimal('purchase_price', 15, 2)->default(0.00);
            $table->date('acquisition_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('product_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->enum('maintenance_type', ['repair', 'cleaning', 'safety_check', 'refurbishment'])->default('repair');
            $table->decimal('cost', 15, 2)->default(0.00);
            $table->text('notes')->nullable();
            $table->date('performed_at')->nullable();
            $table->date('next_check_due')->nullable();
            $table->timestamps();
        });



        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->decimal('quantity', 15, 2);
            $table->enum('direction', ['in', 'out']);
            $table->enum('movement_type', ['sale', 'purchase', 'return', 'damage', 'rental_out'])->default('rental_out');
            $table->integer('reference_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->foreignId('contact_id')->nullable()->constrained()->onDelete('set null');

            $table->string('invoice_number', 50);
            $table->enum('doc_type', ['invoice', 'quote', 'proforma', 'credit_note'])->default('invoice');
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->enum('status', ['unpaid', 'paid', 'partial', 'void'])->default('unpaid');
            $table->string('currency', 10)->default('EUR');
            $table->decimal('exchange_rate', 10, 5)->default(1.00000);
            $table->decimal('subtotal_net', 15, 2)->default(0.00);
            $table->decimal('total_vat_amount', 15, 2)->default(0.00);
            $table->decimal('grand_total_gross', 15, 2)->default(0.00);
            $table->decimal('amount_paid', 15, 2)->default(0.00);
            $table->decimal('balance_due', 15, 2)->default(0.00);
            $table->longText('customer_signature_png')->nullable();
            $table->string('customer_signature_name')->nullable();
            $table->timestamp('signature_timestamp')->nullable();
            $table->string('signature_ip', 45)->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['workspace_id', 'invoice_number'], 'unique_invoice');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description');
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price_net', 15, 2);
            $table->decimal('vat_rate', 5, 2);
            $table->decimal('total_gross', 15, 2);
            $table->timestamps();
        });

        Schema::create('invoice_vat_breakdown', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->decimal('vat_rate', 5, 2);
            $table->decimal('net_amount', 15, 2);
            $table->decimal('vat_amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('payment_method', 50)->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->string('reference')->nullable();
            $table->timestamps();
        });



        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');

            $table->enum('category', ['fuel', 'staff_wages', 'sub_rental', 'marketing', 'utility', 'other']);
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->string('vendor_name')->nullable();
            $table->text('receipt_url')->nullable();
            $table->timestamps();
        });

        Schema::create('call_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->onDelete('cascade');

            $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('call_type', ['inbound', 'outbound', 'missed'])->default('outbound');
            $table->integer('call_duration_seconds')->default(0);
            $table->text('call_notes')->nullable();
            $table->text('call_recording_url')->nullable();
            $table->timestamp('call_date')->useCurrent();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('action_type', ['CREATE', 'UPDATE', 'SIGN', 'DELETE', 'PAY', 'EXPORT']);
            $table->string('entity_name', 50);
            $table->integer('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->integer('related_id');
            $table->enum('related_type', ['invoice', 'contact', 'expense', 'maintenance']);
            $table->text('file_url');
            $table->string('file_name');
            $table->timestamps();
        });

        Schema::create('public_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('share_token', 100)->unique();
            $table->string('password', 100)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('view_count')->default(0);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_shares');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('call_logs');
        Schema::dropIfExists('expenses');

        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_vat_breakdown');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('stock_movements');

        Schema::dropIfExists('product_maintenance');
        Schema::dropIfExists('products');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('workspace_features');
        Schema::dropIfExists('workspace_users');
        Schema::dropIfExists('workspaces');
    }
};
