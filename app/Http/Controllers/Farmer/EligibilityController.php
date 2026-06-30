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
        $programs = Program::where('is_active', true)->with('eligibilityRule')->get();

        $results = $programs->map(function ($program) use ($farmer, $eligibilityService) {
            $evaluation = $farmer
                ? $eligibilityService->evaluate($farmer, $program)
                : ['status' => 'not_eligible', 'missing_requirements' => ['Complete your profile first']];

            return [
                'program' => $program,
                'status' => $evaluation['status'],
                'missing' => $evaluation['missing_requirements'],
            ];
        });

        return view('farmer.eligibility', compact('results', 'farmer'));
    }
}
