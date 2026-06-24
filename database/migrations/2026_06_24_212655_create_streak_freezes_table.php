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
        Schema::create('streak_freezes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('creator_app_id');
            $table->string('streak_type');
            $table->timestamp('earned_at');
            $table->timestamp('used_at')->nullable();
            $table->date('applied_to_date')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'creator_app_id', 'streak_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('streak_freezes');
    }
};
