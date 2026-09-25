<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Distribution;
use App\Models\Farmer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QRVerificationController extends Controller
{
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
        ]);

        $farmer = Farmer::where(
            'qr_code_token',
            $validated['token']
        )->first();

        if (! $farmer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code or farmer not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Get approved applications
        |--------------------------------------------------------------------------
        */
        $applications = Application::with([
                'program',
            ])
            ->where('farmer_id', $farmer->id)
            ->where('status', 'approved')
            ->latest('applied_at')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Prepare approved applications
        |--------------------------------------------------------------------------
        */
        $approvedApplications = $applications->map(function ($application) use ($farmer) {

            $alreadyReleased = Distribution::where('farmer_id', $farmer->id)
                ->where('program_id', $application->program_id)
                ->exists();

            return [
                'application_id' => $application->id,

                'program' => [
                    'id' => $application->program->id,
                    'name' => $application->program->name,
                    'description' => $application->program->description,
                ],

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

    public function release(Request $request): JsonResponse
    {
        
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

        /*
        |--------------------------------------------------------------------------
        | Find farmer using QR token
        |--------------------------------------------------------------------------
        */
        $farmer = Farmer::where(
            'qr_code_token',
            $validated['token']
        )->first();

        if (! $farmer) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid QR code or farmer not found.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Find approved application
        |--------------------------------------------------------------------------
        */
        $application = Application::with('program')
            ->where('id', $validated['application_id'])
            ->where('farmer_id', $farmer->id)
            ->where('status', 'approved')
            ->first();

        if (! $application) {
            return response()->json([
                'success' => false,
                'message' => 'Approved application not found for this farmer.',
            ], 404);
        }

        /*
        |--------------------------------------------------------------------------
        | Prevent duplicate distribution
        |--------------------------------------------------------------------------
        */
        $alreadyReleased = Distribution::where('farmer_id', $farmer->id)
            ->where('program_id', $application->program_id)
            ->exists();

        if ($alreadyReleased) {
            return response()->json([
                'success' => false,
                'message' => 'Benefit already released for this farmer and program.',
            ], 409);
        }

        /*
        |--------------------------------------------------------------------------
        | Create distribution
        |--------------------------------------------------------------------------
        */
        $distribution = Distribution::create([
            'farmer_id' => $farmer->id,
            'program_id' => $application->program_id,
            'released_by' => $request->user()->id,
            'distributed_at' => now(),
            'notes' => $validated['notes'] ?? 'Released through FAMS QR Scanner.',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Benefit released successfully.',
            'distribution' => [
                'id' => $distribution->id,
                'farmer_id' => $distribution->farmer_id,
                'program_id' => $distribution->program_id,
                'program_name' => $application->program->name,
                'distributed_at' => $distribution->distributed_at?->toISOString(),
                'notes' => $distribution->notes,
            ],
        ]);
    }
}