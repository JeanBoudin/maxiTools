<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('twitch_user_id')->unique()->nullable();
            $table->string('twitch_login')->nullable();
            $table->string('twitch_display_name')->nullable();
            $table->string('twitch_avatar')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['twitch_user_id', 'twitch_login', 'twitch_display_name', 'twitch_avatar']);
        });
    }
};
