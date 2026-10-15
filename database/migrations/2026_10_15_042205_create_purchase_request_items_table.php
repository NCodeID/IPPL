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
            $table->foreignId('ingredient_id')->constrained('ingredients')->cascadeOnDelete();
            $table->decimal('estimated_quantity', 12, 2);
            $table->bigInteger('estimated_price')->unsigned();
            $table->decimal('actual_quantity', 12, 2)->nullable();
            $table->bigInteger('actual_price')->unsigned()->nullable();
            $table->bigInteger('estimated_subtotal')->unsigned()->storedAs('estimated_quantity * estimated_price');
            $table->bigInteger('actual_subtotal')->unsigned()->storedAs('actual_quantity * actual_price');
            $table->timestamps();

            $table->unique(['purchase_request_id', 'ingredient_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
    }
};