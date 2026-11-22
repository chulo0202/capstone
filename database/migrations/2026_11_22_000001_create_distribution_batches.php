<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distribution_batches', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('program_benefit_id')->constrained()->restrictOnDelete();
            $table->date('scheduled_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('location', 255);
            $table->text('instructions')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('distribution_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distribution_batch_id')->constrained()->restrictOnDelete();
            $table->foreignId('application_id')->constrained()->restrictOnDelete();
            // A cancelled or removed assignment gives up this unique reservation.
            $table->unsignedBigInteger('active_application_id')->nullable()->unique();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamps();
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->foreignId('distribution_batch_id')->nullable()->constrained()->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('distributions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('distribution_batch_id');
        });
        Schema::dropIfExists('distribution_assignments');
        Schema::dropIfExists('distribution_batches');
    }
};
