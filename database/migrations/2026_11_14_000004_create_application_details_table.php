<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->unique()
                ->constrained('applications')
                ->restrictOnDelete();

            $table->string('form_type', 50);

            $table->json('details');

            $table->timestamps();

            $table->index('form_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_details');
    }
};
