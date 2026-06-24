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
        Schema::create('notification_triggers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('creator_app_id');
            $table->string('trigger_type');
            $table->json('payload');
            $table->timestamp('scheduled_for');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'trigger_type']);
            $table->index(['scheduled_for', 'sent_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_triggers');
    }
};
