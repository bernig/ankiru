<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('accent_color')->nullable()->default('#d97706')->after('openai_api_key');
            $table->boolean('accent_bold')->default(true)->after('accent_color');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['accent_color', 'accent_bold']);
        });
    }
};
