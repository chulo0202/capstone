<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\DistributionReleaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QRVerificationController extends Controller
{
    public function verify(Request $request, DistributionReleaseService $releases): JsonResponse
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
            ->whereHas('program', fn ($program) => $program->whereIn('name', Program::DISTRIBUTION_PROGRAMS))
            ->latest('applied_at')
            ->get();

        $approvedApplications = $applications->map(function (Application $application) use ($releases) {
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
                'already_released' => $releases->alreadyReleased($application),
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
        DistributionReleaseService $releases
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
            'distribution_batch_id' => ['nullable', 'integer', 'exists:distribution_batches,id'],
        ]);

        $result = $releases->release(
            admin: $request->user(),
            applicationId: $validated['application_id'],
            notes: $validated['notes'] ?? 'Released through FAMS QR Scanner.',
            batchId: $validated['distribution_batch_id'] ?? null,
            token: $validated['token'],
        );

        if (isset($result['error'])) {
            [$message, $status] = match ($result['error']) {
                'farmer_not_found' => ['Invalid QR code or farmer not found.', 404],
                'application_not_found' => ['Approved application not found for this farmer.', 404],
                'legacy_application_not_releasable' => ['Legacy program-level applications cannot authorize benefit releases. The farmer must apply for a specific benefit.', 422],
                'pcic_approval_required' => ['Official PCIC approval is required before release.', 422],
                'ineligible' => ['Benefit cannot be released because the farmer is not eligible.', 422],
                'wrong_batch' => ['This application is not assigned to the selected distribution batch.', 409],
                'batch_not_releasable' => ['The assigned distribution must be published and within its scheduled release window.', 409],
                'invalid_benefit' => ['This application does not reference a valid program benefit.', 422],
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
                ...($distribution->distribution_batch_id ? ['distribution_batch_id' => $distribution->distribution_batch_id] : []),
            ],
        ]);
    }
}
