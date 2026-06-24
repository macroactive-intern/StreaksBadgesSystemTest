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
        Schema::create('user_streaks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('creator_app_id');
            $table->string('streak_type');
            $table->unsignedInteger('current_count')->default(0);
            $table->unsignedInteger('longest_count')->default(0);
            $table->date('last_completed_date')->nullable();
            $table->date('last_evaluated_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();

            $table->unique(['user_id', 'creator_app_id', 'streak_type']);
            $table->index(['user_id', 'creator_app_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_streaks');
    }
};
