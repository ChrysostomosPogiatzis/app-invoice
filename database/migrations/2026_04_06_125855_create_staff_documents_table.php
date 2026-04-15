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
        Schema::create('staff_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('file_path');
            $table->string('type')->nullable(); // contract, id, certificate, etc
            $table->date('expiry_date')->nullable();
            $table->timestamps();
        });

        Schema::create('staff_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_member_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_count', 4, 1);
            $table->string('type')->default('annual'); // annual, sick, unpaid
            $table->string('status')->default('pending'); // pending, approved, rejected
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_leave_requests');
        Schema::dropIfExists('staff_documents');
    }
};
