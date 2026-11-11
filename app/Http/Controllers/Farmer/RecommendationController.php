<?php

namespace App\Http\Controllers\Farmer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Distribution;
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

        if ($farmer) {

            $now = now();

            /*
            |--------------------------------------------------------------------------
            | Get Currently Active Programs
            |--------------------------------------------------------------------------
            */
            $programs = Program::where('is_active', true)
                ->whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now)
                ->with('eligibilityRule')
                ->get();

            /*
            |--------------------------------------------------------------------------
            | Evaluate Farmer Eligibility
            |--------------------------------------------------------------------------
            */
            $recommendations = $programs
                ->map(function ($program) use (
                    $farmer,
                    $eligibilityService
                ) {

                    $evaluation = $eligibilityService->evaluate(
                        $farmer,
                        $program
                    );

                    return (object) [
                        'program' => $program,
                        'eligibility_status' => $evaluation['status'],
                        'missing_requirements' =>
                            $evaluation['missing_requirements'],
                    ];
                })

                /*
                |--------------------------------------------------------------------------
                | Only Eligible and Partially Eligible
                |--------------------------------------------------------------------------
                */
                ->filter(function ($recommendation) {

                    return in_array(
                        $recommendation->eligibility_status,
                        [
                            'eligible',
                            'partially_eligible',
                        ],
                        true
                    );
                })

                ->values();

            $applicationStatuses = Application::where(
                    'farmer_id',
                    $farmer->id
                )
                ->pluck('status', 'program_id');
        }

        return view('farmer.recommendations', compact(
            'recommendations',
            'farmer',
            'applicationStatuses'
        ));
    }
}
