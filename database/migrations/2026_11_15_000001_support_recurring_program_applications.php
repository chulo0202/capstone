<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropUnique(
                'applications_farmer_program_scope_unique'
            );

            $table->index(
                ['farmer_id', 'program_id', 'program_benefit_scope_id'],
                'applications_farmer_program_scope_idx'
            );
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->dropUnique(
                'distributions_farmer_program_scope_unique'
            );

            $table->index(
                ['farmer_id', 'program_id', 'program_benefit_scope_id'],
                'distributions_farmer_program_scope_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(
                'applications_farmer_program_scope_idx'
            );

            $table->unique(
                ['farmer_id', 'program_id', 'program_benefit_scope_id'],
                'applications_farmer_program_scope_unique'
            );
        });

        Schema::table('distributions', function (Blueprint $table) {
            $table->dropIndex(
                'distributions_farmer_program_scope_idx'
            );

            $table->unique(
                ['farmer_id', 'program_id', 'program_benefit_scope_id'],
                'distributions_farmer_program_scope_unique'
            );
        });
    }
};
