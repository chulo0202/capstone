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
        Schema::create('rsbsa_farmers', function (Blueprint $table) {
            $table->id();
            $table->string('rsbsa_number')->unique();
            $table->string('full_name');
            $table->string('barangay');
            $table->string('crop_type');
            $table->decimal('land_size', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rsbsa_farmers');
    }
};