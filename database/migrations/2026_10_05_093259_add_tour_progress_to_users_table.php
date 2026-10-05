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
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('tour_completed_at')->nullable()->after('remember_token');
            $table->timestamp('tour_new_file_tip_seen_at')->nullable()->after('tour_completed_at');
            $table->timestamp('tour_first_row_tip_seen_at')->nullable()->after('tour_new_file_tip_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['tour_completed_at', 'tour_new_file_tip_seen_at', 'tour_first_row_tip_seen_at']);
        });
    }
};
