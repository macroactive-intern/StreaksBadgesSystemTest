<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('moderation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('creator_app_id');
            $table->string('detection_type');
            $table->string('severity')->default('medium');  // low, medium, high
            $table->json('payload')->nullable();
            $table->string('status')->default('pending');   // pending, resolved, dismissed
            $table->timestamp('reviewed_at')->nullable();
            $table->unsignedBigInteger('reviewer_id')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['creator_app_id', 'status', 'severity']);
            $table->index(['user_id', 'status']);
            $table->index(['creator_app_id', 'detection_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('moderation_items');
    }
};
