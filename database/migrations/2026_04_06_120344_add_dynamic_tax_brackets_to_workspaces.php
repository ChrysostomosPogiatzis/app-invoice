<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->json('tax_brackets')->nullable();
        });
        
        // Populate initial brackets for existing workspaces
        $defaultBrackets = json_encode([
            ['threshold' => 0, 'rate' => 0],
            ['threshold' => 22000, 'rate' => 20],
            ['threshold' => 32000, 'rate' => 25],
            ['threshold' => 42000, 'rate' => 30],
            ['threshold' => 72000, 'rate' => 35],
        ]);
        
        \DB::table('workspaces')->update(['tax_brackets' => $defaultBrackets]);
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('tax_brackets');
        });
    }
};
