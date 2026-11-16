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
            'PCIC Crop Insurance' => [
                'New Application',
                'Claim for Indemnity',
            ],
            'Seed Subsidies' => [
                'Certified Seeds (Palay/Mais)',
                'Hybrid Seeds',
                'Quality Planting Materials',
            ],
            'Fertilizer Subsidies' => [
                'Complete Fertilizer (NPK/Triple 14)',
                'Urea',
                'Foliar Fertilizer',
            ],
            'Equipment and Machinery Support' => [
                'Combine Harvester',
                '4-Wheel Tractor',
            ],
            'Post-Harvest Support' => [
                'Mechanical/Solar Grain Dryer',
                'Warehouse & Crop Storage',
                'Rice Processing Center (RPC)',
            ],
            'Marketing Support' => [
                'Kadiwa ng Pangulo Market Linkage',
                'Agri-Trade Fair Participation',
                'Bagsakan Center/Trading Post Access',
            ],
            'Training and Technology Demonstrations' => [
                'Techno-Demo Farm Visit (Wet and Dry Season)',
            ],
            'Infrastructure Support' => [
                'Farm-to-Market Roads (FMR)',
                'Communal Irrigation Canal',
            ],
        ];

        DB::transaction(function () use ($approved) {
            // Validate all programs before making any changes.

            $existingProgramCount = DB::table('programs')->count();

            if ($existingProgramCount === 0) {
                // Fresh installation: programs and approved benefits
                // will be created by FamsSeeder after migrations.
                return;
            }

            $programs = DB::table('programs')
                ->whereIn('name', array_keys($approved))
                ->get(['id', 'name']);

            if ($programs->count() !== count($approved)) {
                throw new RuntimeException(
                    'Expected all 9 existing programs. No benefits updated.'
                );
            }


            foreach ($programs as $program) {
                $names = $approved[$program->name];

                // Archive benefits not on the approved list.
                DB::table('program_benefits')
                    ->where('program_id', $program->id)
                    ->whereNotIn('name', $names)
                    ->whereNull('archived_at')
                    ->update([
                        'archived_at' => now(),
                        'updated_at' => now(),
                    ]);

                foreach ($names as $name) {
                    $matches = DB::table('program_benefits')
                        ->where('program_id', $program->id)
                        ->where('name', $name)
                        ->orderBy('id')
                        ->get(['id', 'archived_at']);

                    if ($matches->isEmpty()) {
                        DB::table('program_benefits')->insert([
                            'program_id' => $program->id,
                            'name' => $name,
                            'description' => null,
                            'archived_at' => null,
                            'eligibility_configured_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        continue;
                    }

                    // Preserve the earliest matching benefit ID.
                    $keep = $matches->first();

                    DB::table('program_benefits')
                        ->where('id', $keep->id)
                        ->update([
                            'archived_at' => null,
                            'updated_at' => now(),
                        ]);

                    // Archive duplicate matches without deleting history.
                    $duplicateIds = $matches->skip(1)
                        ->pluck('id')
                        ->all();

                    if ($duplicateIds) {
                        DB::table('program_benefits')
                            ->whereIn('id', $duplicateIds)
                            ->update([
                                'archived_at' => now(),
                                'updated_at' => now(),
                            ]);
                    }
                }
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Automatic rollback disabled to protect historical benefits.'
        );
    }
};
