<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eligibility_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->json('crop_types')->nullable();
            $table->decimal('min_land_size', 10, 2)->nullable();
            $table->decimal('max_land_size', 10, 2)->nullable();
            $table->boolean('requires_rsbsa')->default(false);
            $table->boolean('requires_association')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eligibility_rules');
    }
};
