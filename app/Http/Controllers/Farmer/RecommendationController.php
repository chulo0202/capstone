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
            $programs = Program::available()
                ->whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->whereHas('activeBenefits')
                ->with(['eligibilityRule', 'activeBenefits'])
                ->get();

            $recommendations = $programs
                ->flatMap(function ($program) use ($farmer, $eligibilityService) {
                    return $program->activeBenefits
                        ->filter(fn ($benefit) => $benefit->eligibility_configured_at !== null)
                        ->map(function ($benefit) use ($program, $farmer, $eligibilityService) {
                            $evaluation = $eligibilityService->evaluate($farmer, $program, $benefit);

                            return (object) [
                                'program' => $program,
                                'benefit' => $benefit,
                                'eligibility_status' => $evaluation['status'],
                                'missing_requirements' => $evaluation['missing_requirements'],
                            ];
                        });
                })
                ->filter(fn ($recommendation) => in_array(
                    $recommendation->eligibility_status,
                    ['eligible', 'partially_eligible'],
                    true
                ))
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
