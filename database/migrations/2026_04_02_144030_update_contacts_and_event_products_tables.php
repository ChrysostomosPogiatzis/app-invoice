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
        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('contacts', 'contact_type')) {
                $table->enum('contact_type', ['customer', 'lead', 'vendor', 'individual'])->default('customer')->after('company_name');
            }
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['company_name', 'contact_type']);
        });


    }
};
