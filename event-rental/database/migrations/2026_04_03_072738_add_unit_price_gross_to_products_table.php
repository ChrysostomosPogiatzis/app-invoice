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
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('unit_price_gross', 15, 4)->after('unit_price_net')->nullable();
        });

        // Migrate existing data
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            $gross = round($product->unit_price_net * (1 + ($product->vat_rate / 100)), 4);
            DB::table('products')->where('id', $product->id)->update(['unit_price_gross' => $gross]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit_price_net');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('unit_price_net', 15, 4)->after('unit_price_gross')->nullable();
        });

        // Restore data
        $products = DB::table('products')->get();
        foreach ($products as $product) {
            $net = round($product->unit_price_gross / (1 + ($product->vat_rate / 100)), 4);
            DB::table('products')->where('id', $product->id)->update(['unit_price_net' => $net]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('unit_price_gross');
        });
    }
};
