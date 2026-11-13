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

        $programs = Program::available()
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->whereHas('activeBenefits')
            ->with(['eligibilityRule', 'activeBenefits.eligibilityRules'])
            ->get();

        $results = $programs->flatMap(function ($program) use ($farmer, $eligibilityService) {
            return $program->activeBenefits->map(function ($benefit) use (
                $program,
                $farmer,
                $eligibilityService
            ) {
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
