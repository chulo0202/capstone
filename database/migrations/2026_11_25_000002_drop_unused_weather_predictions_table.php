<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('weather_predictions');
    }

    public function down(): void
    {
        throw new RuntimeException('Restore the database backup to recover weather predictions.');
    }
};
