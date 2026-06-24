<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaderboard_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_app_id');
            $table->string('leaderboard_type');   // weekly_workout, monthly_streak, volume_lifted
            $table->string('period_key');          // e.g. "2026-W25" or "2026-06"
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('score')->default(0);
            $table->unsignedInteger('rank');
            $table->string('nickname', 50)->nullable();
            $table->timestamp('snapped_at')->useCurrent();

            $table->unique(['creator_app_id', 'leaderboard_type', 'period_key', 'user_id']);
            $table->index(['creator_app_id', 'leaderboard_type', 'period_key', 'rank']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_snapshots');
    }
};
