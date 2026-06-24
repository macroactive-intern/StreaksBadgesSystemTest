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
        Schema::create('streak_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_app_id');
            $table->string('streak_type');
            $table->boolean('enabled')->default(true);
            $table->string('qualifying_event_type');
            $table->unsignedInteger('minimum_threshold')->default(1);
            $table->json('reward_config')->nullable();
            $table->timestamps();

            $table->unique(['creator_app_id', 'streak_type']);
            $table->index('creator_app_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streak_configs');
    }
};
