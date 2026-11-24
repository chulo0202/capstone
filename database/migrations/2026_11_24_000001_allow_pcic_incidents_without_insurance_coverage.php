<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pcic_damage_incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('pcic_insurance_coverage_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('pcic_damage_incidents', function (Blueprint $table) {
            $table->unsignedBigInteger('pcic_insurance_coverage_id')->nullable(false)->change();
        });
    }
};
