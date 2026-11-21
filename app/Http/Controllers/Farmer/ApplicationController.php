<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProgramApplicationRequest;
use App\Models\Application;
use App\Models\AuditLog;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\EligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Models\PcicInsuranceCoverage;
use App\Models\PcicDamageIncident;

class ApplicationController extends Controller
{
    public function index(): View
    {
        $farmer = Auth::user()->farmer;

        $applications = $farmer
            ? Application::with(['program', 'benefit', 'pcicDamageIncident', 'latestPcicTrackingUpdate'])
                ->where(fn ($query) => $query->withCurrentBenefit()->orWhereHas(
                    'program',
                    fn ($program) => $program->where('name', 'PCIC Crop Insurance')
                ))
                ->where('farmer_id', $farmer->id)
                ->latest('applied_at')
                ->get()
            : collect();

        return view('farmer.applications', compact('applications', 'farmer'));
    }

    public function store(
        StoreProgramApplicationRequest $request,
        Program $program,
        EligibilityService $eligibilityService
    ): RedirectResponse {
        $validated = $request->validated();

        $farmer = Auth::user()->farmer;

        if (! $farmer || ! $farmer->is_active) {
            abort(403, 'Inactive or missing farmer accounts cannot submit applications.');
        }

        if (! $farmer->profile_completed) {
            return back()->with('error', 'Please complete your profile before applying.');
        }

        $newDamagePhotoPath = null;

        try {
            $result = DB::transaction(function () use (
                $request,
                $program,
                $farmer,
                $validated,
                $eligibilityService,
                &$newDamagePhotoPath
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

                $benefit = $lockedProgram->activeBenefits()
                    ->whereKey($validated['program_benefit_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $benefit || ! $benefit->isAvailableToFarmers($lockedProgram, now())) {
                    return null;
                }

                $isPcic = $lockedProgram->isPcicCropInsurance();
                $isPcicClaim = $isPcic && $benefit->name === 'Claim for Indemnity';

                if (
                    $isPcic
                    && $benefit->name === 'New Application'
                    && (float) $validated['area_to_insure'] > (float) $lockedFarmer->land_size
                ) {
                    return [
                        'error' => "Area to be insured cannot exceed the land size in My Profile ({$lockedFarmer->land_size} hectares).",
                    ];
                }

                $profileDetails = [
                    'farmer_name' => $lockedFarmer->full_name,
                    'farmer_barangay' => $lockedFarmer->barangay,
                    'crop_type' => $lockedFarmer->crop_type,
                ];

                if ($isPcic && collect($profileDetails)->contains(fn ($value) => blank($value))) {
                    return ['error' => 'Complete your name, barangay, and crop type in your farmer profile before applying.'];
                }

                $evaluation = $eligibilityService->evaluate(
                    $lockedFarmer,
                    $lockedProgram,
                    $benefit
                );

                if ($evaluation['status'] !== 'eligible') {
                    $requirements = implode('; ', array_unique($evaluation['missing_requirements']));

                    return ['error' => 'You cannot apply for this benefit yet. Missing requirements: '.$requirements.'.'];
                }

                // Preserve one-application-per-benefit behavior for
                // programs that do not support recurring applications.
                // PCIC claims will use incident-based duplicate screening.
                if (! $isPcicClaim) {
                    $existingApplication = Application::query()
                        ->where('farmer_id', $lockedFarmer->id)
                        ->where('program_id', $lockedProgram->id)
                        ->where('program_benefit_id', $benefit->id)
                        ->first();

                    if ($existingApplication) {
                        return [
                            'application' => $existingApplication,
                            'created' => false,
                        ];
                    }
                }

                // Validate verified PCIC insurance before accepting a claim.
                if ($isPcicClaim) {
                    $coverage = PcicInsuranceCoverage::query()
                        ->where('farmer_id', $lockedFarmer->id)
                        ->whereKey($validated['pcic_coverage_id'])
                        ->where('verification_status', 'verified')
                        ->lockForUpdate()
                        ->first();

                    if (! $coverage || ! $coverage->isVerified()) {
                        return [
                            'error' => 'A verified PCIC insurance policy is required.',
                        ];
                    }

                    $farmerCrop = trim((string) $lockedFarmer->crop_type);
                    $insuredCrop = trim((string) $coverage->crop_type);

                    if (
                        $farmerCrop === ''
                        || $insuredCrop === ''
                        || strcasecmp($farmerCrop, $insuredCrop) !== 0
                    ) {
                        return [
                            'error' => 'The insured crop does not match your farmer profile. Please contact the administrator to verify your insurance details.',
                        ];
                    }

                    $dateOfLoss = \Carbon\Carbon::parse(
                        $validated['date_of_loss']
                    )->startOfDay();

                    if (
                        $dateOfLoss->lt($coverage->coverage_start_date)
                        || $dateOfLoss->gt($coverage->coverage_end_date)
                    ) {
                        return [
                            'error' => 'The date of loss is outside the insurance coverage period.',
                        ];
                    }

                    if (
                        (float) $validated['area_damaged']
                        > (float) $coverage->area_insured
                    ) {
                        return [
                            'error' => 'The damaged area exceeds the insured area.',
                        ];
                    }

                    // Screen for possible duplicate PCIC damage incidents.
                    // Same policy, loss date, and cause require review.
                    $possibleDuplicate = PcicDamageIncident::query()
                        ->where('farmer_id', $lockedFarmer->id)
                        ->where('pcic_insurance_coverage_id', $coverage->id)
                        ->whereDate(
                            'date_of_loss',
                            $validated['date_of_loss']
                        )
                        ->where(
                            'cause_of_loss',
                            $validated['cause_of_loss']
                        )
                        ->exists();

                    if ($possibleDuplicate) {
                        return [
                            'error' => 'A possible duplicate damage incident was found for this insurance policy and date. Please contact the administrator for review before submitting another claim.',
                        ];
                    }

                }

                $details = collect($validated)
                    ->except(['program_benefit_id', 'damage_photo', 'pcic_coverage_id'])
                    ->all();

                if ($isPcic) {
                    $details += $profileDetails;
                }

                if ($isPcicClaim) {
                    $details = array_replace($details, [
                        'insurance_reference' => $coverage->policy_number,
                        'crop_type' => $coverage->crop_type,
                        'area_insured' => $coverage->area_insured,
                        'coverage_start_date' => $coverage->coverage_start_date->toDateString(),
                        'coverage_end_date' => $coverage->coverage_end_date->toDateString(),
                    ]);
                }

                // Store claim evidence in the private R2 bucket.
                if ($isPcicClaim && $request->hasFile('damage_photo')) {
                    $newDamagePhotoPath = $request->file('damage_photo')
                        ->store(
                            'pcic-claims/'.$lockedFarmer->id,
                            'private'
                        );

                    if (! $newDamagePhotoPath) {
                        throw new \RuntimeException('Damage photo upload failed.');
                    }

                    $details['damage_photo_path'] = $newDamagePhotoPath;
                }

                $application = Application::create([
                    'farmer_id' => $lockedFarmer->id,
                    'program_id' => $lockedProgram->id,
                    'program_benefit_id' => $benefit->id,
                    'status' => 'pending',
                    'remarks' => 'Submitted through farmer portal',
                    'applied_at' => now(),
                ]);

                $formType = match (true) {
                    $isPcicClaim => 'pcic_claim',
                    $isPcic => 'pcic_new_application',
                    $lockedProgram->name === 'Agricultural Loans'
                        => 'agricultural_loan',
                    in_array($lockedProgram->name, [
                        'Seed Subsidies',
                        'Fertilizer Subsidies',
                    ], true) => 'claim_intent',
                    default => 'service_request',
                };

                $application->detail()->create([
                    'form_type' => $formType,
                    'details' => $details,
                ]);


                // Create a PCIC damage incident for each new claim.
                // This runs inside the same database transaction.
                if ($isPcicClaim) {
                    $incident = PcicDamageIncident::create([
                        'farmer_id' => $lockedFarmer->id,
                        'pcic_insurance_coverage_id' => $coverage->id,
                        'incident_reference' => PcicDamageIncident::generateReference(),
                        'date_of_loss' => $validated['date_of_loss'],
                        'cause_of_loss' => $validated['cause_of_loss'],
                        'crop_type' => $coverage->crop_type,
                        'area_damaged' => $validated['area_damaged'],
                        'status' => 'pending',
                        'application_id' => $application->id,
                        'reviewed_by' => null,
                        'reviewed_at' => null,
                        'review_notes' => null,
                    ]);

                    AuditLog::create([
                        'user_id' => $lockedFarmer->user_id,
                        'action' => 'PCIC_INCIDENT_REPORTED',
                        'model' => 'PcicDamageIncident',
                        'model_id' => $incident->id,
                        'details' => [
                            'incident_reference' => $incident->incident_reference,
                            'application_id' => $application->id,
                            'pcic_insurance_coverage_id' => $coverage->id,
                        ],
                    ]);
                }

                AuditLog::create([
                    'user_id' => $lockedFarmer->user_id,
                    'action' => 'APPLICATION_SUBMITTED',
                    'model' => 'Application',
                    'model_id' => $application->id,
                    'details' => [
                        'program_id' => $lockedProgram->id,
                        'program_benefit_id' => $benefit->id,
                        'form_type' => $formType,
                    ],
                ]);

                return [
                    'application' => $application,
                    'created' => true,
                ];
            });

        } catch (\Throwable $exception) {
            if ($newDamagePhotoPath) {
                try {
                    if (! Storage::disk('private')->delete($newDamagePhotoPath)) {
                        Log::warning('Failed to clean up PCIC claim photo.', [
                            'farmer_id' => $farmer->id,
                        ]);
                    }
                } catch (\Throwable $cleanupException) {
                    Log::error('PCIC claim photo cleanup failed.', [
                        'farmer_id' => $farmer->id,
                        'exception' => get_class($cleanupException),
                    ]);
                }
            }

            Log::error('Farmer application submission failed.', [
                'farmer_id' => $farmer->id,
                'program_id' => $program->id,
                'exception' => get_class($exception),
            ]);

            throw $exception;
        }

        if (! $result) {
            return back()->with(
                'error',
                'This benefit is unavailable or you do not meet its eligibility requirements.'
            );
        }

        if (isset($result['error'])) {
            return back()->with('error', $result['error']);
        }

        if (! $result['created']) {
            return back()->with('info', 'You already applied for this benefit.');
        }

        if ($result['application']->detail?->form_type === 'pcic_claim') {
            return redirect()
                ->to(route('farmer.applications.index').'#application-'.$result['application']->id)
                ->with('success', 'Your indemnity claim was submitted successfully. Track your claim below.');
        }

        return back()->with('success', 'Application submitted successfully.');
    }
}
