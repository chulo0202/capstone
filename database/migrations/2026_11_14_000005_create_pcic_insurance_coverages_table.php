<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pcic_insurance_coverages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farmer_id')
                ->constrained('farmers')
                ->restrictOnDelete();

            $table->string('policy_number', 100)->unique();
            $table->string('crop_type', 100);
            $table->decimal('area_insured', 10, 2);

            $table->date('coverage_start_date');
            $table->date('coverage_end_date');

            $table->string('verification_status', 20)
                ->default('pending');

            $table->text('rejection_reason')->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->index([
                'farmer_id',
                'verification_status',
            ], 'pcic_coverage_farmer_status_idx');
        });

        DB::statement("
            ALTER TABLE pcic_insurance_coverages
            ADD CONSTRAINT pcic_coverage_area_positive
            CHECK (area_insured > 0)
        ");

        DB::statement("
            ALTER TABLE pcic_insurance_coverages
            ADD CONSTRAINT pcic_coverage_dates_valid
            CHECK (coverage_end_date >= coverage_start_date)
        ");

        DB::statement("
            ALTER TABLE pcic_insurance_coverages
            ADD CONSTRAINT pcic_coverage_status_valid
            CHECK (
                verification_status IN (
                    'pending',
                    'verified',
                    'rejected'
                )
            )
        ");

        DB::statement("
            ALTER TABLE pcic_insurance_coverages
            ADD CONSTRAINT pcic_coverage_verification_consistent
            CHECK (
                (
                    verification_status = 'verified'
                    AND verified_by IS NOT NULL
                    AND verified_at IS NOT NULL
                )
                OR
                (
                    verification_status <> 'verified'
                    AND verified_at IS NULL
                )
            )
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('pcic_insurance_coverages');
    }
};
