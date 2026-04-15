<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('workspaces', 'default_invoice_notes')) {
            Schema::table('workspaces', function (Blueprint $table) {
                $table->text('default_invoice_notes')->nullable();
            });
        }

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'notes')) {
                $table->text('notes')->nullable();
            }
            if (!Schema::hasColumn('invoices', 'terms')) {
                $table->text('terms')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('default_invoice_notes');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['notes', 'terms']);
        });
    }
};
