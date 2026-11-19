<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\EligibilityService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class EligibilityController extends Controller
{
    public function index(EligibilityService $eligibilityService): View
    {
        $farmer = Auth::user()->farmer;
        $now = now();

        $programs = Program::forFarmerRecommendations()->get();

        $results = $programs->flatMap(function ($program) use ($farmer, $eligibilityService, $now) {
            return $program->activeBenefits
                ->filter(fn ($benefit) => $benefit->isAvailableToFarmers($program, $now))
                ->map(function ($benefit) use ($program, $farmer, $eligibilityService, $now) {
                    $configuration = $benefit->configurations->first(fn ($configuration) => $configuration->isCurrentAt($now));

                    $evaluation = $farmer
                        ? $eligibilityService->evaluate($farmer, $program, $benefit)
                        : [
                            'status' => 'not_eligible',
                            'missing_requirements' => ['Complete your profile first'],
                            'global' => ['status' => 'not_eligible', 'missing_requirements' => []],
                            'benefit' => ['status' => 'not_eligible', 'missing_requirements' => []],
                        ];

                    return [
                        'program' => $program,
                        'benefit' => $benefit,
                        'start_at' => $configuration?->start_at ?? $program->start_at,
                        'end_at' => $configuration?->end_at ?? $program->end_at,
                        'status' => $evaluation['status'],
                        'missing' => $evaluation['missing_requirements'],
                        'global' => $evaluation['global'],
                        'benefit_evaluation' => $evaluation['benefit'],
                    ];
                });
        })->values();

        return view('farmer.eligibility', compact('results', 'farmer'));
    }
}
