<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 2: leaderboard_visible and leaderboard_nickname are stored here but
// the leaderboard endpoints are not exposed until Phase 2 is implemented.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('creator_app_id');
            $table->string('leaderboard_nickname', 50)->nullable();
            $table->boolean('leaderboard_visible')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'creator_app_id']);
            $table->index(['creator_app_id', 'leaderboard_visible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
