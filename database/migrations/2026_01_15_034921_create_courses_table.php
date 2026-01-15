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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable(); // Image Path
            $table->string('instructor')->nullable();

            // Price & Reward
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('reward_points')->default(0);
            $table->string('duration')->nullable(); // e.g., "3 hours", "45 minutes"
            // Source (Local vs Wix)
            $table->string('source')->default('local'); // 'local', 'wix'
            $table->string('wix_course_id')->nullable();
            $table->boolean('is_published')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
