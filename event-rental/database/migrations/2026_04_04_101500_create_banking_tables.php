<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banking_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('provider');       // 'vivawallet' | 'mypos' | future providers
            $table->string('label');          // e.g. "Main POS Terminal"
            $table->boolean('is_active')->default(true);
            $table->text('credentials');      // encrypted JSON — provider-specific keys
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('banking_connection_id')->constrained()->cascadeOnDelete();

            $table->string('external_id')->nullable();
            $table->string('provider');
            $table->dateTime('transaction_date');
            $table->string('type')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('EUR');
            $table->string('status')->nullable();
            $table->string('card_type')->nullable();
            $table->string('card_last4')->nullable();
            $table->string('reference')->nullable();
            $table->string('description')->nullable();
            $table->json('raw_payload')->nullable();

            // Reconciliation link — nullable polymorphic to Invoice or Expense
            $table->string('linked_type')->nullable();  // 'invoice' | 'expense'
            $table->unsignedBigInteger('linked_id')->nullable();
            $table->index(['linked_type', 'linked_id']);

            $table->timestamps();

            $table->unique(['banking_connection_id', 'external_id']);
            $table->index(['workspace_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('banking_connections');
    }
};
