<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            // Change category to string so we can have custom payroll categories
            $table->string('category', 50)->change();
            
            $table->foreignId('staff_member_id')->nullable()->after('workspace_id')->constrained('staff_members')->nullOnDelete();
            
            $table->boolean('is_payroll')->default(false)->after('staff_member_id');
            $table->decimal('gross_salary', 15, 2)->nullable();
            $table->decimal('si_employee', 15, 2)->nullable();
            $table->decimal('si_employer', 15, 2)->nullable();
            $table->decimal('gesi_employee', 15, 2)->nullable();
            $table->decimal('gesi_employer', 15, 2)->nullable();
            $table->decimal('tax_employee', 15, 2)->nullable();
            $table->decimal('net_payable', 15, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['staff_member_id']);
            $table->dropColumn([
                'staff_member_id', 'is_payroll', 'gross_salary', 
                'si_employee', 'si_employer', 'gesi_employee', 
                'gesi_employer', 'tax_employee', 'net_payable'
            ]);
            // Reverting category to original enum
            $table->enum('category', ['fuel', 'staff_wages', 'sub_rental', 'marketing', 'utility', 'other'])->change();
        });
    }
};
