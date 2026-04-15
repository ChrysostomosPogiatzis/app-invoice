<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->decimal('si_employee_rate', 5, 2)->default(8.80)->change();
            $table->decimal('si_employer_rate', 5, 2)->default(12.50)->change();
            $table->decimal('annual_tax_threshold', 15, 2)->default(22000.00)->change();
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->decimal('si_employee_rate', 5, 2)->default(8.30)->change();
            $table->decimal('si_employer_rate', 5, 2)->default(12.00)->change();
            $table->decimal('annual_tax_threshold', 15, 2)->default(19500.00)->change();
        });
    }
};
