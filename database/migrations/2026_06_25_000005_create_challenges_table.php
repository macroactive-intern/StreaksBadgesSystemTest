<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_app_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('challenge_type');      // e.g. event_count, score, streak_count
            $table->json('config')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index(['creator_app_id', 'starts_at', 'ends_at']);
        });

        Schema::create('challenge_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('challenge_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('score')->default(0);
            $table->timestamps();

            $table->unique(['challenge_id', 'user_id']);
            $table->index(['challenge_id', 'score']);

            $table->foreign('challenge_id')->references('id')->on('challenges')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_entries');
        Schema::dropIfExists('challenges');
    }
};
