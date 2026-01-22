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
    public function up()
    {

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value')->nullable();
            $table->timestamps();
        });


        DB::table('settings')->insert([
            ['key' => 'reward_points_per_usd', 'value' => '10', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'redeem_points_per_usd', 'value' => '100', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::table('users', function (Blueprint $table) {
            $table->integer('current_points')->default(0)->after('email');
        });

        Schema::create('point_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->integer('points');
            $table->string('type');
            $table->string('description')->nullable();

            // Polymorphic Fields
            $table->nullableMorphs('rewardable');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('point_histories');
        Schema::dropIfExists('settings');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('current_points');
        });
    }
};
