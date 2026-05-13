<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('csv_drafts', function (Blueprint $table) {
            $table->index(['user_id', 'last_accessed_at'], 'csv_drafts_user_last_accessed_index');
        });
    }

    public function down(): void
    {
        Schema::table('csv_drafts', function (Blueprint $table) {
            $table->dropIndex('csv_drafts_user_last_accessed_index');
        });
    }
};
