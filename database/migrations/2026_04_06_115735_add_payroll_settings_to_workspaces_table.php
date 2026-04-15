<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->decimal('si_employee_rate', 5, 2)->default(8.3);
            $table->decimal('si_employer_rate', 5, 2)->default(12.0);
            $table->decimal('gesi_employee_rate', 5, 2)->default(2.65);
            $table->decimal('gesi_employer_rate', 5, 2)->default(2.90);
            $table->decimal('annual_tax_threshold', 15, 2)->default(19500.00);
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'si_employee_rate', 'si_employer_rate', 
                'gesi_employee_rate', 'gesi_employer_rate', 
                'annual_tax_threshold'
            ]);
        });
    }
};
