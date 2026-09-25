<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('eligibility_rules', function (Blueprint $table) {
            $table->foreignId('program_benefit_id')
                ->nullable()
                ->after('program_id')
                ->constrained('program_benefits')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('eligibility_rules', function (Blueprint $table) {
            $table->dropForeign(['program_benefit_id']);
            $table->dropColumn('program_benefit_id');
        });
    }
};