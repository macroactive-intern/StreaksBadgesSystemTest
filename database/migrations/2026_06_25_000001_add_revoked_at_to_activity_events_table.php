<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->timestamp('revoked_at')->nullable()->after('source_id');
            $table->index('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('activity_events', function (Blueprint $table) {
            $table->dropIndex(['revoked_at']);
            $table->dropColumn('revoked_at');
        });
    }
};
