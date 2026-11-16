<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pcic_insurance_coverages', function (Blueprint $table) {
            $table->string('verification_evidence_reference', 255)
                ->nullable();

            $table->text('verification_notes')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('pcic_insurance_coverages', function (Blueprint $table) {
            $table->dropColumn([
                'verification_evidence_reference',
                'verification_notes',
            ]);
        });
    }
};
