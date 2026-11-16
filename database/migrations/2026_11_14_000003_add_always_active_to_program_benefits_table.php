<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_benefits', function (Blueprint $table) {
            $table->boolean('always_active')->default(false);
        });

        // Only PCIC Claim for Indemnity bypasses the normal
        // program activation and application schedule.
        DB::table('program_benefits')
            ->where('name', 'Claim for Indemnity')
            ->whereIn('program_id', function ($query) {
                $query->select('id')
                    ->from('programs')
                    ->where('name', 'PCIC Crop Insurance');
            })
            ->update([
                'always_active' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        Schema::table('program_benefits', function (Blueprint $table) {
            $table->dropColumn('always_active');
        });
    }
};
