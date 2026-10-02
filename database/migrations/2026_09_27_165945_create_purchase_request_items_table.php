<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->decimal('estimated_quantity', 10, 2);
            $table->bigInteger('estimated_price');
            $table->decimal('actual_quantity', 10, 2)->nullable();
            $table->bigInteger('actual_price')->nullable();
            $table->bigInteger('subtotal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};
