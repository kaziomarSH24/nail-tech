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
        Schema::create('user_appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('client_name');
            $table->string('client_phone')->nullable();
            $table->string('service_type');
            $table->dateTime('appointment_at'); // Date & Time combined
            $table->text('notes')->nullable();

            // Reminder Logic
            $table->boolean('is_reminder_on')->default(false);
            $table->integer('reminder_minutes_before')->default(60); // 1 hour before
            $table->boolean('is_reminder_sent')->default(false);

            $table->enum('status', ['upcoming', 'completed', 'cancelled'])->default('upcoming');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_appointments');
    }
};
