<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Distribution;
use App\Models\DistributionAssignment;
use App\Models\DistributionBatch;
use App\Models\Farmer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DistributionReleaseService
{
    public function __construct(
        private EligibilityService $eligibility,
        private AuditLogService $audit,
    ) {}

    public function alreadyReleased(Application $application): bool
    {
        return Distribution::query()
            ->where('application_id', $application->id)
            ->orWhere(fn ($query) => $query->where('farmer_id', $application->farmer_id)
                ->where('program_id', $application->program_id)
                ->where('program_benefit_id', $application->program_benefit_id))
            ->exists();
    }

    public function applicationError(Application $application, Farmer $farmer): ?string
    {
        if ($application->status !== 'approved' || (int) $application->farmer_id !== (int) $farmer->id) {
            return 'application_not_found';
        }

        if (! $application->program_benefit_id) {
            return 'legacy_application_not_releasable';
        }

        if (! $application->program || (int) $application->benefit?->program_id !== (int) $application->program_id) {
            return 'invalid_benefit';
        }

        if ($this->alreadyReleased($application)) {
            return 'already_released';
        }

        if (
            $farmer->is_active && ! $application->benefit->archived_at && ! $application->program->archived_at
            && $this->eligibility->evaluate($farmer, $application->program, $application->benefit)['status'] !== 'eligible'
        ) {
            return 'ineligible';
        }

        return null;
    }

    public function release(
        User $admin,
        ?int $applicationId,
        ?string $notes,
        ?int $batchId = null,
        ?string $token = null,
        ?int $farmerId = null,
        ?int $programId = null,
    ): array {
        abort_unless($admin->isAdmin(), 403);

        return DB::transaction(function () use ($admin, $applicationId, $notes, $batchId, $token, $farmerId, $programId) {
            $candidate = $applicationId ? Application::find($applicationId) : null;
            // Serialize releases for the same farmer, including different applications for one benefit.
            $farmer = $token !== null
                ? Farmer::where('qr_code_token', $token)->lockForUpdate()->first()
                : Farmer::lockForUpdate()->find($farmerId ?? $candidate?->farmer_id);

            if (! $farmer) {
                return ['error' => $token !== null ? 'farmer_not_found' : 'application_not_found'];
            }

            $application = Application::with(['program', 'benefit'])
                ->where('farmer_id', $farmer->id)
                ->when($applicationId, fn ($query) => $query->whereKey($applicationId),
                    fn ($query) => $query->where('status', 'approved')->whereNotNull('program_benefit_id'))
                ->when($programId, fn ($query) => $query->where('program_id', $programId))
                ->latest('id')->lockForUpdate()->first();

            if (! $application) {
                return ['error' => 'application_not_found'];
            }

            if ($error = $this->applicationError($application, $farmer)) {
                return ['error' => $error];
            }

            $assignment = DistributionAssignment::where('active_application_id', $application->id)->first();
            $batch = null;

            if ($assignment) {
                $batch = DistributionBatch::lockForUpdate()->findOrFail($assignment->distribution_batch_id);
                $assignment->refresh();
            }

            if ($batchId !== null && (! $assignment?->active_application_id || (int) $batch->id !== $batchId)) {
                return ['error' => 'wrong_batch'];
            }

            if ($assignment?->active_application_id) {
                if (
                    ! $batch->canRelease() || $assignment->released_at || $assignment->cancelled_at
                    || (int) $batch->program_id !== (int) $application->program_id
                    || (int) $batch->program_benefit_id !== (int) $application->program_benefit_id
                ) {
                    return ['error' => 'batch_not_releasable'];
                }
            } else {
                $batch = null;
            }

            $distribution = Distribution::create([
                'farmer_id' => $farmer->id,
                'program_id' => $application->program_id,
                'program_benefit_id' => $application->program_benefit_id,
                'application_id' => $application->id,
                'distribution_batch_id' => $batch?->id,
                'released_by' => $admin->id,
                'distributed_at' => now(),
                'notes' => $notes,
            ]);

            if ($batch) {
                $assignment->update(['released_at' => $distribution->distributed_at]);

                if (! $batch->assignments()->whereNotNull('active_application_id')->whereNull('released_at')->exists()) {
                    $batch->update(['status' => 'completed']);
                }
            }

            $this->audit->log('BENEFIT_RELEASED', $distribution, "Application {$application->id} released.");

            return compact('distribution', 'application');
        }, 3);
    }
}
