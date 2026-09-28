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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('runner_id')->constrained('users');
            $table->foreignId('chef_id')->nullable()->constrained('users');
            $table->foreignId('table_id')->nullable()->constrained('tables');
            $table->string('customer_name')->nullable();
            $table->string('order_type');
            $table->string('status');
            $table->string('payment_status');
            $table->string('note')->nullable();
            $table->bigInteger('subtotal');
            $table->bigInteger('discount');
            $table->bigInteger('tax');
            $table->bigInteger('total');
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
