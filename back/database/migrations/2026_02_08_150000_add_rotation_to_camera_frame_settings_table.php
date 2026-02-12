<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('camera_frame_settings', function (Blueprint $table) {
            $table->boolean('rotation_enabled')->default(false);
            $table->unsignedInteger('rotation_interval_seconds')->default(12);
            $table->json('rotation_items')->nullable();
            $table->string('now_playing_title')->default('Musique en cours');
            $table->string('now_playing_empty')->default('Rien en lecture');
        });
    }

    public function down(): void
    {
        Schema::table('camera_frame_settings', function (Blueprint $table) {
            $table->dropColumn([
                'rotation_enabled',
                'rotation_interval_seconds',
                'rotation_items',
                'now_playing_title',
                'now_playing_empty',
            ]);
        });
    }
};
