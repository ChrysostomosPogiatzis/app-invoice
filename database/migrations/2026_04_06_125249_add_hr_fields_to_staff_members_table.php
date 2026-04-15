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
        Schema::table('staff_members', function (Blueprint $table) {
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->string('si_number')->nullable()->after('phone');
            $table->string('tax_id')->nullable()->after('si_number');
            $table->string('iban')->nullable()->after('tax_id');
            $table->date('joining_date')->nullable()->after('iban');
            $table->string('emergency_contact_name')->nullable()->after('joining_date');
            $table->string('emergency_contact_phone')->nullable()->after('emergency_contact_name');
            $table->integer('annual_leave_total')->default(20)->after('emergency_contact_phone');
            $table->decimal('leave_balance', 8, 2)->default(20.00)->after('annual_leave_total');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn([
                'email', 'phone', 'si_number', 'tax_id', 'iban', 'joining_date', 
                'emergency_contact_name', 'emergency_contact_phone', 
                'annual_leave_total', 'leave_balance'
            ]);
        });
    }
};
