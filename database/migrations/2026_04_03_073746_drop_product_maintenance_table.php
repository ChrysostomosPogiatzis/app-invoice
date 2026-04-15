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
        Schema::dropIfExists('product_maintenance');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
    }
};
