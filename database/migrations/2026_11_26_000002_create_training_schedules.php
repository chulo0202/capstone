<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('training_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('topic');
            $table->text('objectives');
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('venue');
            $table->string('trainer');
            $table->unsignedInteger('slots');
            $table->text('instructions')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('training_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(['training_session_id', 'farmer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_registrations');
        Schema::dropIfExists('training_sessions');
    }
};
