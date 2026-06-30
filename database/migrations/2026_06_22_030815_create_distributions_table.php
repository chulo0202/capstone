<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('released_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('distributed_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['farmer_id', 'program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributions');
    }
};
