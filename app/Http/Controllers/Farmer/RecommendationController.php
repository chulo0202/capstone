<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Program;
use App\Services\EligibilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function index(EligibilityService $eligibilityService): View
    {
        $farmer = Auth::user()->farmer;
        $recommendations = collect();
        $applicationStatuses = collect();

        if ($farmer && $farmer->profile_completed && $farmer->is_active) {
            $now = now();

            $programs = Program::forFarmerRecommendations()->get();

            $recommendations = $programs
                ->flatMap(function ($program) use ($farmer, $eligibilityService, $now) {
                    return $program->activeBenefits
                        ->filter(fn ($benefit) => $benefit->isAvailableToFarmers($program, $now))
                        ->map(function ($benefit) use ($program, $farmer, $eligibilityService, $now) {
                            $evaluation = $eligibilityService->evaluate(
                                $farmer,
                                $program,
                                $benefit
                            );

                            $configuration = $benefit->configurations->first(fn ($config) => $config->isCurrentAt($now));

                            return (object) [
                                'program' => $program,
                                'benefit' => $benefit,
                                'claim_locked' => $program->isPcicCropInsurance() && $benefit->name === 'Claim for Indemnity'
                                    && ! Application::hasApprovedPcicNewApplication($farmer->id, $program->id),
                                'always_open' => $benefit->isOpenYearRound($program),
                                'start_at' => $configuration?->start_at ?? $program->start_at,
                                'end_at' => $configuration?->end_at ?? $program->end_at,
                                'eligibility_status' => $evaluation['status'],
                                'missing_requirements' => $evaluation['missing_requirements'],
                            ];
                        });
                })
                ->values();

            $applicationStatuses = Application::where('farmer_id', $farmer->id)
                ->get(['program_id', 'program_benefit_id', 'status'])
                ->mapWithKeys(fn (Application $application) => [
                    "{$application->program_id}:{$application->program_benefit_id}" => $application->status,
                ]);
        }

        return view('farmer.recommendations', compact(
            'recommendations',
            'farmer',
            'applicationStatuses'
        ));
    }
}
