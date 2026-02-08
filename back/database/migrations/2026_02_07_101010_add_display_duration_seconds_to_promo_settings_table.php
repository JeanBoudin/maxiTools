<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_settings', function (Blueprint $table) {
            $table->unsignedInteger('display_duration_seconds')->default(8)->after('interval_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('promo_settings', function (Blueprint $table) {
            $table->dropColumn('display_duration_seconds');
        });
    }
};
