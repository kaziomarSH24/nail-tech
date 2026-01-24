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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->decimal('course_price', 10, 2)->default(0.00)->after('course_id');
            $table->enum('status', ['pending', 'active', 'cancelled'])->default('pending')->after('course_price');
            $table->integer('redeemed_points')->default(0)->after('status');
            $table->decimal('discount_amount', 10, 2)->default(0.00)->after('redeemed_points');
            $table->decimal('final_amount', 10, 2)->default(0.00)->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropColumn(['course_price', 'status', 'redeemed_points', 'discount_amount', 'final_amount']);
        });
    }
};
