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
            $table->foreignId('user_id')->constrained();
            $table->string('status')->default('pending'); // pending, paid, failed
            $table->string('payment_intent_id')->nullable();
            $table->decimal('total_amount', 10, 2);
            $table->json('items'); // [{variant_id: 123, quantity: 1, price: 50}]
            $table->json('shipping_address');
            $table->string('shopify_order_id')->nullable(); 

            $table->timestamps();
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
