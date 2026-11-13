<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\EligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(): View
    {
        $farmer = Auth::user()->farmer;

        $applications = $farmer
            ? Application::with(['program', 'benefit'])
                ->where('farmer_id', $farmer->id)
                ->latest('applied_at')
                ->get()
            : collect();

        return view('farmer.applications', compact('applications', 'farmer'));
    }

    public function store(
        Request $request,
        Program $program,
        EligibilityService $eligibilityService
    ): RedirectResponse {
        $validated = $request->validate([
            'program_benefit_id' => ['required', 'integer', 'exists:program_benefits,id'],
        ]);

        $farmer = Auth::user()->farmer;

        if (! $farmer || $farmer->is_active !== true) {
            abort(403, 'Inactive or missing farmer accounts cannot submit applications.');
        }

        if (! $farmer->profile_completed) {
            return back()->with('error', 'Please complete your profile before applying.');
        }

        $now = now();
        if (
            ! $program->is_active
            || $program->archived_at
            || ! $program->start_at
            || ! $program->end_at
            || $program->start_at->gt($now)
            || $program->end_at->lt($now)
        ) {
            return back()->with('error', 'This program is not currently accepting applications.');
        }

        $application = DB::transaction(function () use (
            $program,
            $farmer,
            $validated,
            $eligibilityService
        ) {
            $lockedFarmer = Farmer::query()
                ->whereKey($farmer->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedFarmer->is_active || ! $lockedFarmer->profile_completed) {
                return null;
            }

            $lockedProgram = Program::query()
                ->whereKey($program->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                ! $lockedProgram->is_active
                || $lockedProgram->archived_at
                || ! $lockedProgram->start_at
                || ! $lockedProgram->end_at
                || $lockedProgram->start_at->gt(now())
                || $lockedProgram->end_at->lt(now())
            ) {
                return null;
            }

            $benefit = $lockedProgram->activeBenefits()
                ->whereKey($validated['program_benefit_id'])
                ->lockForUpdate()
                ->first();

            if (! $benefit || ! $benefit->eligibility_configured_at) {
                return null;
            }

            $evaluation = $eligibilityService->evaluate($lockedFarmer, $lockedProgram, $benefit);
            if ($evaluation['status'] !== 'eligible') {
                return null;
            }

            $application = Application::firstOrCreate(
                [
                    'farmer_id' => $farmer->id,
                    'program_id' => $lockedProgram->id,
                    'program_benefit_id' => $benefit->id,
                ],
                [
                    'status' => 'pending',
                    'remarks' => 'Submitted through farmer portal',
                    'applied_at' => now(),
                ]
            );

            if ($application->wasRecentlyCreated) {
                AuditLog::create([
                    'user_id' => $lockedFarmer->user_id,
                    'action' => 'APPLICATION_SUBMITTED',
                    'model' => 'Application',
                    'model_id' => $application->id,
                    'details' => [
                        'program_id' => $lockedProgram->id,
                        'program_benefit_id' => $benefit->id,
                    ],
                ]);
            }

            return $application;
        });

        if (! $application) {
            return back()->with(
                'error',
                'This benefit is unavailable or you do not meet its eligibility requirements.'
            );
        }

        if (! $application->wasRecentlyCreated) {
            return back()->with('info', 'You already applied for this benefit.');
        }

        return back()->with('success', 'Application submitted successfully.');
    }
}
