<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('platform_challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('challenge_type');
            $table->json('config')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamps();

            $table->index(['starts_at', 'ends_at']);
        });

        Schema::create('platform_challenge_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('platform_challenge_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('creator_app_id');
            $table->unsignedBigInteger('score')->default(0);
            $table->timestamps();

            $table->unique(['platform_challenge_id', 'user_id']);
            $table->index(['platform_challenge_id', 'score']);

            $table->foreign('platform_challenge_id')
                ->references('id')->on('platform_challenges')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('platform_challenge_entries');
        Schema::dropIfExists('platform_challenges');
    }
};
