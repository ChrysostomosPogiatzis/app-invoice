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
        Schema::table('workspaces', function (Blueprint $table) {
            $table->decimal('redundancy_rate', 5, 2)->default(1.20)->after('provident_employer_rate');
            $table->decimal('training_rate', 5, 2)->default(0.50)->after('redundancy_rate');
            $table->decimal('holiday_rate', 5, 2)->default(0)->after('training_rate'); // Default 0 as it's sector-specific
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->decimal('union_rate', 5, 2)->nullable()->after('provident_employer_rate');
            $table->string('union_type')->nullable()->after('union_rate'); // PEO / SEK
            $table->boolean('use_holiday_fund')->default(false)->after('union_type');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('redundancy_amount', 10, 2)->nullable()->after('provident_employer');
            $table->decimal('training_amount', 10, 2)->nullable()->after('redundancy_amount');
            $table->decimal('holiday_amount', 10, 2)->nullable()->after('training_amount');
            $table->decimal('union_amount', 10, 2)->nullable()->after('holiday_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['redundancy_rate', 'training_rate', 'holiday_rate']);
        });

        Schema::table('staff_members', function (Blueprint $table) {
            $table->dropColumn(['union_rate', 'union_type', 'use_holiday_fund']);
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['redundancy_amount', 'training_amount', 'holiday_amount', 'union_amount']);
        });
    }
};
