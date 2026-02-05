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
        Schema::create('user_inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Product Info
            $table->string('item_name');
            $table->string('image')->nullable();
            $table->string('source')->default('external'); // 'shop' or 'external'
            $table->foreignId('linked_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->integer('current_quantity')->default(0);
            $table->integer('reorder_threshold')->default(2);
            $table->integer('reminder_frequency_days')->nullable();
            $table->timestamp('last_restocked_at')->nullable();
            $table->timestamp('next_reminder_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_inventories');
    }
};
