<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_configurations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('program_benefit_id')
                ->constrained('program_benefits')
                ->restrictOnDelete();

            $table->text('description');

            $table->dateTime('start_at');
            $table->dateTime('end_at');

            $table->json('eligibility_snapshot')->nullable();

            $table->timestamps();

            $table->index(
                ['program_benefit_id', 'start_at', 'end_at'],
                'benefit_configurations_schedule_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('benefit_configurations');
    }
};
