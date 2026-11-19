<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $approved = [
            'Agricultural Loans' => [
                'Agri-Negosyo (ANYO) Loan',
                'Survival and Recovery (SURE) Loan',
                'Agri-Puhunan at Pantawid',
                'Young Agripreneurs Program (YAP)',
            ],
            'Equipment and Machinery Support' => [
                'Combine Harvester',
                '4-Wheel Tractor',
            ],
            'Fertilizer Subsidies' => [
                'Urea',
                'Complete Fertilizer (NPK/Triple 14)',
                'Foliar Fertilizer',
            ],
            'Infrastructure Support' => [
                'Farm-to-Market Roads (FMR)',
                'Communal Irrigation Canal',
            ],
            'Marketing Support' => [
                'Kadiwa ng Pangulo Market Linkage',
                'Agri-Trade Fair Participation',
                'Bagsakan Center/Trading Post Access',
            ],
            'PCIC Crop Insurance' => [
                'New Application',
                'Claim for Indemnity',
            ],
            'Post-Harvest Support' => [
                'Mechanical/Solar Grain Dryer',
                'Warehouse & Crop Storage',
                'Rice Processing Center (RPC)',
            ],
            'Seed Subsidies' => [
                'Certified Seeds (Palay/Mais)',
                'Hybrid Seeds',
                'Quality Planting Materials',
            ],
            'Training and Technology Demonstrations' => [
                'Techno-Demo Farm Visit (Wet and Dry Season)',
            ],
        ];

        DB::transaction(function () use ($approved) {
            $programs = DB::table('programs')
                ->whereIn('name', array_keys($approved))
                ->get(['id', 'name']);

            foreach ($programs as $program) {
                foreach ($approved[$program->name] as $name) {
                    $existing = DB::table('program_benefits')
                        ->where('program_id', $program->id)
                        ->where('name', $name)
                        ->orderByRaw('CASE WHEN archived_at IS NULL THEN 0 ELSE 1 END')
                        ->orderBy('id')
                        ->first();

                    $alwaysActive = (
                        $program->name === 'PCIC Crop Insurance'
                        && $name === 'Claim for Indemnity'
                    );

                    if ($existing) {
                        DB::table('program_benefits')
                            ->where('id', $existing->id)
                            ->update([
                                'archived_at' => null,
                                'always_active' => $alwaysActive,
                                'updated_at' => now(),
                            ]);

                        DB::table('program_benefits')
                            ->where('program_id', $program->id)
                            ->where('name', $name)
                            ->where('id', '!=', $existing->id)
                            ->whereNull('archived_at')
                            ->update(['archived_at' => now(), 'updated_at' => now()]);
                    } else {
                        DB::table('program_benefits')->insert([
                            'program_id' => $program->id,
                            'name' => $name,
                            'description' => null,
                            'always_active' => $alwaysActive,
                            'archived_at' => null,
                            'eligibility_configured_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                DB::table('program_benefits')
                    ->where('program_id', $program->id)
                    ->whereNotIn('name', $approved[$program->name])
                    ->whereNull('archived_at')
                    ->update([
                        'archived_at' => now(),
                        'always_active' => false,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Automatic rollback disabled to protect program history.'
        );
    }
};
