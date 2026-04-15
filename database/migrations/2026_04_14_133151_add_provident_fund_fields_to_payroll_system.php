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
        // 1. Workspace defaults
        Schema::table('workspaces', function (Blueprint $table) {
            $table->decimal('provident_employee_rate', 5, 2)->default(0)->after('gesi_employer_rate');
            $table->decimal('provident_employer_rate', 5, 2)->default(0)->after('provident_employee_rate');
        });

        // 2. Staff Member overrides
        Schema::table('staff_members', function (Blueprint $table) {
            $table->decimal('provident_employee_rate', 5, 2)->nullable()->after('base_salary');
            $table->decimal('provident_employer_rate', 5, 2)->nullable()->after('provident_employee_rate');
        });

        // 3. Expense records
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('provident_employee', 10, 2)->nullable()->after('tax_employee');
            $table->decimal('provident_employer', 10, 2)->nullable()->after('provident_employee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['provident_employee_rate', 'provident_employer_rate']);
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn(['provident_employee_rate', 'provident_employer_rate']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['provident_employee', 'provident_employer']);
        });
    }
};
