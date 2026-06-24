<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            // Phase 1: both default to false/public. Privacy controls activate in Phase 2.
            $table->boolean('privacy_hidden')->default(false)->after('revoke_reason');
            $table->boolean('is_featured')->default(false)->after('privacy_hidden');

            $table->index(['user_id', 'creator_app_id', 'privacy_hidden']);
            $table->index(['user_id', 'creator_app_id', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::table('user_badges', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'creator_app_id', 'privacy_hidden']);
            $table->dropIndex(['user_id', 'creator_app_id', 'is_featured']);
            $table->dropColumn(['privacy_hidden', 'is_featured']);
        });
    }
};
