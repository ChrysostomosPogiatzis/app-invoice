<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();

            $table->string('quote_number')->unique();
            $table->date('date');
            $table->date('valid_until');
            $table->enum('status', ['draft', 'sent', 'viewed', 'accepted', 'declined', 'expired', 'converted'])->default('draft');
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('subtotal_net', 10, 2);
            $table->decimal('total_vat_amount', 10, 2);
            $table->decimal('grand_total_gross', 10, 2);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();

            // Track if converted to invoice
            $table->foreignId('converted_to_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};