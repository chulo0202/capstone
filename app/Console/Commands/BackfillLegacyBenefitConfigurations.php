<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BackfillLegacyBenefitConfigurations extends Command
{
    protected $signature = 'benefits:backfill-legacy {--execute : Insert verified historical configurations}';

    protected $description = 'Safely backfill verified benefit configurations from historical audit logs';

    public function handle(): int
    {
        $auditIds = [21, 22, 23, 25];
        $expectedBenefits = [
            21 => 69,
            22 => 63,
            23 => 58,
            25 => 59,
        ];

        $execute = (bool) $this->option('execute');

        if ($execute && app()->environment('production')) {
            $this->error('Production execution is disabled.');
            return self::FAILURE;
        }

        if ($execute && DB::connection()->getDatabaseName() !== 'fams_benefits_migration_test') {
            $this->error('Execution is restricted to the designated local test database.');
            return self::FAILURE;
        }

        $this->info($execute ? 'EXECUTE MODE' : 'DRY-RUN MODE');

        try {
            $process = function () use ($auditIds, $expectedBenefits, $execute): void {
                foreach ($auditIds as $auditId) {
                    $audit = DB::table('audit_logs')
                        ->where('id', $auditId)
                        ->where('model', 'Program')
                        ->where('action', 'created')
                        ->first();

                    if (! $audit) {
                        throw new RuntimeException("Audit {$auditId} not found.");
                    }

                    $details = json_decode($audit->details, true, 512, JSON_THROW_ON_ERROR);

                    if (! is_array($details)) {
                        throw new RuntimeException("Invalid audit details for {$auditId}.");
                    }

                    $benefitId = (int) ($details['program_benefit_id'] ?? 0);
                    $programId = (int) ($details['program_id'] ?? 0);

                    if ($benefitId !== $expectedBenefits[$auditId] ||
                        $programId !== (int) $audit->model_id) {
                        throw new RuntimeException("Audit {$auditId} does not match expected benefit/program.");
                    }

                    $benefit = DB::table('program_benefits')
                        ->where('id', $benefitId)
                        ->where('program_id', $programId)
                        ->whereNull('archived_at')
                        ->first();

                    if (! $benefit || ! $benefit->eligibility_configured_at) {
                        throw new RuntimeException("Benefit {$benefitId} is missing or not configured.");
                    }

                    foreach (['description', 'start_at', 'end_at'] as $field) {
                        if (! isset($details[$field]) || ! is_string($details[$field]) ||
                            trim($details[$field]) === '') {
                            throw new RuntimeException("Audit {$auditId} has invalid {$field}.");
                        }
                    }

                    $start = \Carbon\Carbon::parse($details['start_at']);
                    $end = \Carbon\Carbon::parse($details['end_at']);

                    if ($end->lessThanOrEqualTo($start)) {
                        throw new RuntimeException("Audit {$auditId} has invalid schedule.");
                    }

                    $startAt = $start->format('Y-m-d H:i:s');
                    $endAt = $end->format('Y-m-d H:i:s');

                    $exists = DB::table('benefit_configurations')
                        ->where('program_benefit_id', $benefitId)
                        ->where('start_at', $startAt)
                        ->where('end_at', $endAt)
                        ->exists();

                    if ($exists) {
                        $this->line("SKIP: Benefit {$benefitId} already has this schedule.");
                        continue;
                    }

                    $this->line("READY: Audit {$auditId} -> Benefit {$benefitId} ({$startAt} to {$endAt})");

                    if ($execute) {
                        DB::table('benefit_configurations')->insert([
                            'program_benefit_id' => $benefitId,
                            'description' => $details['description'],
                            'start_at' => $startAt,
                            'end_at' => $endAt,
                            'eligibility_snapshot' => json_encode(
                                $details['eligibility'] ?? [],
                                JSON_THROW_ON_ERROR
                            ),
                            'created_at' => $audit->created_at,
                            'updated_at' => $audit->created_at,
                        ]);
                    }
                }
            };

            if ($execute) {
                DB::transaction($process);
                $this->info('Verified historical configurations inserted successfully.');
            } else {
                $process();
                $this->info('Dry run completed. No records inserted.');
            }

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }
    }
}
