<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('weather_predictions', function (Blueprint $table) {
            $table->decimal('pressure', 8, 2)->nullable();
            $table->decimal('rainfall', 8, 2)->nullable();
            $table->integer('clouds')->nullable();
            $table->string('weather_icon')->nullable();
            $table->string('alert_level')->default('Normal');
        });
    }

    public function down(): void
    {
        Schema::table('weather_predictions', function (Blueprint $table) {
            $table->dropColumn([
                'pressure',
                'rainfall',
                'clouds',
                'weather_icon',
                'alert_level',
            ]);
        });
    }
};