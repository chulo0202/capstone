<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Services\AuditLogService;
use App\Services\EligibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QRVerificationController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate(['token' => ['required', 'string']]);
        $farmer = Farmer::where('qr_code_token', $validated['token'])->first();

        if (! $farmer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code or farmer not found.',
            ], 404);
        }

        $applications = Application::with(['program', 'benefit'])
            ->where('farmer_id', $farmer->id)
            ->where('status', 'approved')
            ->latest('applied_at')
            ->get();

        $approvedApplications = $applications->map(function (Application $application) use ($farmer) {
            $alreadyReleased = Distribution::query()
                ->where(function ($query) use ($application) {
                    $query->where('application_id', $application->id)
                        ->orWhere(function ($scope) use ($application) {
                            $scope->where('farmer_id', $application->farmer_id)
                                ->where('program_id', $application->program_id);

                            if ($application->program_benefit_id === null) {
                                $scope->whereNull('program_benefit_id');
                            } else {
                                $scope->where('program_benefit_id', $application->program_benefit_id);
                            }
                        });
                })
                ->exists();

            return [
                'application_id' => $application->id,
                'program' => [
                    'id' => $application->program->id,
                    'name' => $application->program->name,
                    'description' => $application->program->description,
                ],
                'benefit' => $application->benefit ? [
                    'id' => $application->benefit->id,
                    'name' => $application->benefit->name,
                    'description' => $application->benefit->description,
                ] : null,
                'program_benefit_id' => $application->program_benefit_id,
                'status' => $application->status,
                'applied_at' => $application->applied_at?->toISOString(),
                'remarks' => $application->remarks,
                'already_released' => $alreadyReleased,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Farmer verified successfully.',
            'farmer' => [
                'id' => $farmer->id,
                'full_name' => $farmer->full_name,
                'barangay' => $farmer->barangay,
                'crop_type' => $farmer->crop_type,
                'land_size' => $farmer->land_size,
            ],
            'approved_applications' => $approvedApplications,
        ]);
    }

    public function release(
        Request $request,
        EligibilityService $eligibilityService,
        AuditLogService $auditLogService
    ): JsonResponse {
        if ($request->user()->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only admin users can release benefits.',
            ], 403);
        }

        $validated = $request->validate([
            'token' => ['required', 'string'],
            'application_id' => ['required', 'integer', 'exists:applications,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $result = DB::transaction(function () use (
            $request,
            $validated,
            $eligibilityService,
            $auditLogService
        ) {
            $farmer = Farmer::query()
                ->where('qr_code_token', $validated['token'])
                ->lockForUpdate()
                ->first();

            if (! $farmer) {
                return ['error' => 'farmer_not_found'];
            }

            $application = Application::query()
                ->with(['program', 'benefit'])
                ->whereKey($validated['application_id'])
                ->where('farmer_id', $farmer->id)
                ->lockForUpdate()
                ->first();

            if (! $application || $application->status !== 'approved') {
                return ['error' => 'application_not_found'];
            }

            if ($application->program_benefit_id === null) {
                return ['error' => 'legacy_application_not_releasable'];
            }


            $evaluation = $eligibilityService->evaluate(
                $farmer,
                $application->program,
                $application->benefit
            );

            if ($evaluation['status'] !== 'eligible') {
                return ['error' => 'ineligible'];
            }

            $alreadyReleased = Distribution::query()
                ->where(function ($query) use ($application) {
                    $query->where('application_id', $application->id)
                        ->orWhere(function ($scope) use ($application) {
                            $scope->where('farmer_id', $application->farmer_id)
                                ->where('program_id', $application->program_id);

                            if ($application->program_benefit_id === null) {
                                $scope->whereNull('program_benefit_id');
                            } else {
                                $scope->where('program_benefit_id', $application->program_benefit_id);
                            }
                        });
                })
                ->exists();

            if ($alreadyReleased) {
                return ['error' => 'already_released'];
            }

            $distribution = Distribution::create([
                'farmer_id' => $farmer->id,
                'program_id' => $application->program_id,
                'program_benefit_id' => $application->program_benefit_id,
                'application_id' => $application->id,
                'released_by' => $request->user()->id,
                'distributed_at' => now(),
                'notes' => $validated['notes'] ?? 'Released through FAMS QR Scanner.',
            ]);

            $auditLogService->log(
                'BENEFIT_RELEASED',
                $distribution,
                "Application {$application->id} released for " .
                    ($application->benefit?->name ?? 'legacy program-level benefit') . ' through QR scanner.'
            );

            return ['distribution' => $distribution, 'application' => $application];
        });

        if (isset($result['error'])) {
            [$message, $status] = match ($result['error']) {
                'farmer_not_found' => ['Invalid QR code or farmer not found.', 404],
                'application_not_found' => ['Approved application not found for this farmer.', 404],
                'legacy_application_not_releasable' => ['Legacy program-level applications cannot authorize benefit releases. The farmer must apply for a specific benefit.', 422],
                'ineligible' => ['Benefit cannot be released because the farmer is not eligible.', 422],
                default => ['Benefit already released for this application.', 409],
            };

            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        /** @var Distribution $distribution */
        $distribution = $result['distribution'];
        $application = $result['application'];

        return response()->json([
            'success' => true,
            'message' => 'Benefit released successfully.',
            'distribution' => [
                'id' => $distribution->id,
                'farmer_id' => $distribution->farmer_id,
                'program_id' => $distribution->program_id,
                'program_name' => $application->program->name,
                'program_benefit_id' => $distribution->program_benefit_id,
                'benefit_name' => $application->benefit?->name,
                'application_id' => $distribution->application_id,
                'distributed_at' => $distribution->distributed_at?->toISOString(),
                'notes' => $distribution->notes,
            ],
        ]);
    }
}
