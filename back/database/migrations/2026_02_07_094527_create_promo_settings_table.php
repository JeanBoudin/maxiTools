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
        Schema::create('promo_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('twitch_account_id')->constrained('twitch_accounts')->cascadeOnDelete();
            $table->json('lines');
            $table->unsignedInteger('interval_minutes')->default(10);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_settings');
    }
};
