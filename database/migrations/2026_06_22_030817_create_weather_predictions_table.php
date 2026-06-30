<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('weather_predictions', function (Blueprint $table) {
            $table->id();
            $table->string('location');
            $table->decimal('temperature', 5, 2)->nullable();
            $table->integer('humidity')->nullable();
            $table->string('description')->nullable();
            $table->string('weather_main')->nullable();
            $table->decimal('wind_speed', 5, 2)->nullable();
            $table->boolean('rain_alert')->default(false);
            $table->boolean('storm_alert')->default(false);
            $table->text('advisory')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('weather_predictions');
    }
};
