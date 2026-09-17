<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\EligibilityService;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DistributionController extends Controller
{
    public function index(): View
    {
        return view('admin.distributions', [
            'distributions' => Distribution::with(['farmer', 'program', 'releasedBy'])
                ->latest('distributed_at')
                ->paginate(10),

            'programs' => Program::where('is_active', true)
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function scan(): View
    {
        return view('admin.distributions-scan');
    }

    public function verify(
    Request $request,
    EligibilityService $eligibilityService
): View|RedirectResponse {

    $request->validate([
        'qr_data' => 'required|string',
    ]);

    $data = json_decode($request->qr_data, true);

    /*
     * Validate QR payload.
     * The QR code now contains only the secure token.
     */
    if (! $data || ! isset($data['token'])) {
        return back()->with('error', 'Invalid QR code data.');
    }

    /*
     * Find the farmer using the QR token.
     */
    $farmer = Farmer::with('user')
        ->where('qr_code_token', $data['token'])
        ->first();

    /*
     * Verify farmer.
     */
    if (! $farmer) {
        return back()->with(
            'error',
            'Farmer verification failed. QR code is invalid.'
        );
    }

    $programs = Program::where('is_active', true)
        ->get()
        ->map(function ($program) use ($farmer, $eligibilityService) {

            /*
             * 1. Check eligibility
             */
            $evaluation = $eligibilityService->evaluate(
                $farmer,
                $program
            );

            /*
             * 2. Check farmer application
             */
            $application = Application::where('farmer_id', $farmer->id)
                ->where('program_id', $program->id)
                ->latest('applied_at')
                ->first();

            /*
             * 3. Check if benefit was already released
             */
            $alreadyReleased = Distribution::where('farmer_id', $farmer->id)
                ->where('program_id', $program->id)
                ->exists();

            return [
                'program' => $program,
                'status' => $evaluation['status'],
                'missing' => $evaluation['missing_requirements'],
                'application' => $application,
                'application_status' => $application?->status,
                'already_released' => $alreadyReleased,
            ];
        });

    return view(
        'admin.distributions-verify',
        compact('farmer', 'programs')
    );
}

    public function release(
        Request $request,
        EligibilityService $eligibilityService,
        AuditLogService $auditLogService
    ): RedirectResponse {

        $validated = $request->validate([
            'farmer_id' => 'required|exists:farmers,id',
            'program_id' => 'required|exists:programs,id',
            'notes' => 'nullable|string|max:500',
        ]);

        /*
         * Find farmer
         */
        $farmer = Farmer::findOrFail($validated['farmer_id']);

        /*
         * Find program
         */
        $program = Program::findOrFail($validated['program_id']);

        /*
         * Check eligibility again before releasing.
         * This prevents bypassing the UI.
         */
        $evaluation = $eligibilityService->evaluate(
            $farmer,
            $program
        );

        if ($evaluation['status'] !== 'eligible') {
            return back()->with(
                'error',
                'Benefit cannot be released because the farmer is not eligible.'
            );
        }

        /*
         * Check approved application
         */
        $application = Application::where('farmer_id', $farmer->id)
            ->where('program_id', $program->id)
            ->where('status', 'approved')
            ->latest('applied_at')
            ->first();

        if (! $application) {
            return back()->with(
                'error',
                'Benefit cannot be released because the farmer does not have an approved application.'
            );
        }

        /*
         * Prevent duplicate distribution
         */
        $exists = Distribution::where('farmer_id', $farmer->id)
            ->where('program_id', $program->id)
            ->exists();

        if ($exists) {
            return back()->with(
                'error',
                'Benefit already released for this farmer and program.'
            );
        }

        /*
         * Create distribution record
         */
        $distribution = Distribution::create([
            'farmer_id' => $farmer->id,
            'program_id' => $program->id,
            'released_by' => $request->user()->id,
            'distributed_at' => now(),
            'notes' => $validated['notes'] ?? null,
        ]);

        $auditLogService->log(
            'BENEFIT_RELEASED',
            $distribution,
            'Benefit released to farmer for program.'
        );

        return redirect()
            ->route('admin.distributions.index')
            ->with(
                'success',
                'Benefit released successfully.'
            );
    }
}