<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('api_usage_logs', function (Blueprint $table) {
            $table->index(['user_id', 'operation'], 'api_usage_logs_user_operation_index');
        });
    }

    public function down(): void
    {
        Schema::table('api_usage_logs', function (Blueprint $table) {
            $table->dropIndex('api_usage_logs_user_operation_index');
        });
    }
};
