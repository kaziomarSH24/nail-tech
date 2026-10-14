<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (!Schema::hasColumn('lessons', 'section_name')) {
                $table->string('section_name')->nullable()->default('General')->after('course_id');
            }
            if (!Schema::hasColumn('lessons', 'lesson_type')) {
                $table->string('lesson_type')->default('video')->after('description');
            }
            if (!Schema::hasColumn('lessons', 'image_path')) {
                $table->string('image_path')->nullable()->after('lesson_type');
            }
        });

        // Make video_path nullable to support article/image-only lessons
        try {
            DB::statement('ALTER TABLE lessons MODIFY video_path VARCHAR(255) NULL');
        } catch (\Throwable $e) {
            // Fallback for sqlite or environments where alter statement might differ
            if (DB::getDriverName() === 'sqlite') {
                // SQLite handles nullable by default in schema changes
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'image_path')) {
                $table->dropColumn('image_path');
            }
            if (Schema::hasColumn('lessons', 'lesson_type')) {
                $table->dropColumn('lesson_type');
            }
            if (Schema::hasColumn('lessons', 'section_name')) {
                $table->dropColumn('section_name');
            }
        });
    }
};
