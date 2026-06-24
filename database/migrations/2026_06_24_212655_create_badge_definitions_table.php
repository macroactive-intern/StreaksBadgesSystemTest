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
        Schema::create('badge_definitions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('creator_app_id')->nullable();
            $table->string('name');
            $table->text('description');
            $table->string('badge_category');
            $table->string('icon');
            $table->string('rule_type');
            $table->json('rule_config');
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index('creator_app_id');
            $table->index('rule_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_definitions');
    }
};
