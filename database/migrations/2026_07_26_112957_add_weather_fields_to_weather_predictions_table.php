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
    Schema::table('weather_predictions', function (Blueprint $table) {

        $table->decimal('pressure', 8, 2)->nullable()->after('wind_speed');

        $table->decimal('rainfall', 8, 2)->nullable()->after('pressure');

        $table->integer('clouds')->nullable()->after('rainfall');

        $table->string('weather_icon')->nullable()->after('weather_main');

        $table->string('alert_level')->default('Normal')->after('storm_alert');

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
