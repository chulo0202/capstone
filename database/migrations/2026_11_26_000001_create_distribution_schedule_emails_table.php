<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_schedule_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('farmer_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->string('gmail_message_id')->nullable();
            $table->timestamps();
            $table->unique(['distribution_batch_id', 'farmer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distribution_schedule_emails');
    }
};
