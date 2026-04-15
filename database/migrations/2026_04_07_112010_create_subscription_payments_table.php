<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('subscription_payments', function (Blueprint $col) {
            $col->id();
            $col->foreignId('workspace_id')->constrained()->onDelete('cascade');
            $col->decimal('amount', 10, 2)->nullable();
            $col->string('payment_method')->nullable()->default('manual');
            $col->timestamp('billed_at')->useCurrent();
            $col->timestamp('extended_until');
            $col->string('notes')->nullable();
            $col->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('subscription_payments');
    }
};
