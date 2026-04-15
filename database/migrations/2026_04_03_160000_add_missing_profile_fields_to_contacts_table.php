<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            if (! Schema::hasColumn('contacts', 'company_name')) {
                $table->string('company_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('contacts', 'address')) {
                $table->text('address')->nullable()->after('vat_number');
            }

            if (! Schema::hasColumn('contacts', 'contact_type')) {
                $table->string('contact_type')->default('customer')->after('address');
            }
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $columnsToDrop = [];

            if (Schema::hasColumn('contacts', 'company_name')) {
                $columnsToDrop[] = 'company_name';
            }

            if (Schema::hasColumn('contacts', 'address')) {
                $columnsToDrop[] = 'address';
            }

            if (Schema::hasColumn('contacts', 'contact_type')) {
                $columnsToDrop[] = 'contact_type';
            }

            if ($columnsToDrop !== []) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
