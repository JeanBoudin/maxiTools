<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obs_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('twitch_account_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('host')->default('host.docker.internal');
            $table->unsignedInteger('port')->default(4455);
            $table->string('password')->nullable();
            $table->boolean('use_tls')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obs_settings');
    }
};
