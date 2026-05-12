<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('csv_drafts', function (Blueprint $table) {
            $table->timestamp('last_accessed_at')->nullable()->after('has_csv_loaded');
        });
    }

    public function down(): void
    {
        Schema::table('csv_drafts', function (Blueprint $table) {
            $table->dropColumn('last_accessed_at');
        });
    }
};
