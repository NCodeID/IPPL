<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods')->cascadeOnDelete();
            $table->foreignId('cashier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->bigInteger('amount')->unsigned();
            $table->bigInteger('tendered_amount')->unsigned()->nullable();
            $table->bigInteger('change_amount')->unsigned()->nullable();
            $table->enum('status', ['pending', 'success', 'failed', 'expired'])->default('pending');
            $table->string('snap_token')->nullable();
            $table->string('payment_reference')->unique();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
