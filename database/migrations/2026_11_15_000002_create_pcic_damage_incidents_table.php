<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Required for composite foreign keys.
        Schema::table('pcic_insurance_coverages', function (Blueprint $table) {
            $table->unique(
                ['id', 'farmer_id'],
                'pcic_coverage_id_farmer_unique'
            );
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->unique(
                ['id', 'farmer_id'],
                'applications_id_farmer_unique'
            );
        });

        Schema::create('pcic_damage_incidents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('farmer_id')
                ->constrained('farmers')
                ->restrictOnDelete();

            $table->unsignedBigInteger('pcic_insurance_coverage_id');

            $table->string('incident_reference', 50)->unique();

            $table->date('date_of_loss');
            $table->string('cause_of_loss', 30);
            $table->string('crop_type', 100);
            $table->decimal('area_damaged', 10, 2);

            $table->string('status', 30)->default('pending');

            $table->unsignedBigInteger('application_id')
                ->nullable()
                ->unique();

            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            $table->timestamps();

            $table->foreign(
                ['pcic_insurance_coverage_id', 'farmer_id'],
                'pcic_incident_coverage_owner_fk'
            )
                ->references(['id', 'farmer_id'])
                ->on('pcic_insurance_coverages')
                ->restrictOnDelete();

            $table->foreign(
                ['application_id', 'farmer_id'],
                'pcic_incident_application_owner_fk'
            )
                ->references(['id', 'farmer_id'])
                ->on('applications')
                ->restrictOnDelete();

            $table->index(
                ['farmer_id', 'pcic_insurance_coverage_id', 'date_of_loss'],
                'pcic_incident_farmer_policy_date_idx'
            );
        });

        DB::statement("
            ALTER TABLE pcic_damage_incidents
            ADD CONSTRAINT pcic_incident_area_positive
            CHECK (area_damaged > 0)
        ");

        DB::statement("
            ALTER TABLE pcic_damage_incidents
            ADD CONSTRAINT pcic_incident_status_valid
            CHECK (status IN ('pending', 'verified', 'rejected'))
        ");

        DB::statement("
            ALTER TABLE pcic_damage_incidents
            ADD CONSTRAINT pcic_incident_cause_valid
            CHECK (cause_of_loss IN ('Typhoon', 'Flood', 'Drought', 'Pest'))
        ");
    }

    public function down(): void
    {
        if (
            Schema::hasTable('pcic_damage_incidents')
            && DB::table('pcic_damage_incidents')->exists()
        ) {
            throw new RuntimeException(
                'Cannot roll back PCIC damage incidents while historical incident records exist.'
            );
        }

        Schema::dropIfExists('pcic_damage_incidents');

        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique('applications_id_farmer_unique');
        });

        Schema::table('pcic_insurance_coverages', function (Blueprint $table) {
            $table->dropUnique('pcic_coverage_id_farmer_unique');
        });
    }
};
