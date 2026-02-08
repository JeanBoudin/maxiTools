<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('camera_frame_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('twitch_account_id')->constrained('twitch_accounts')->cascadeOnDelete();
            $table->boolean('show_sub_goal')->default(false);
            $table->string('sub_goal_title')->nullable();
            $table->unsignedInteger('sub_goal_current')->default(0);
            $table->unsignedInteger('sub_goal_target')->default(10);
            $table->string('sub_goal_subtitle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_frame_settings');
    }
};
