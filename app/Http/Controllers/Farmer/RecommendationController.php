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

            $programs = Program::query()
                ->whereNull('archived_at')
                ->whereHas('activeBenefits')
                ->where(function ($query) use ($now) {
                    $query->where(function ($normal) use ($now) {
                        $normal->where('is_active', true)
                            ->whereNotNull('start_at')
                            ->whereNotNull('end_at')
                            ->where('start_at', '<=', $now)
                            ->where('end_at', '>=', $now);
                    })->orWhere('name', 'PCIC Crop Insurance')
                    ->orWhereHas('activeBenefits', function ($benefits) {
                        $benefits->where('always_active', true);
                    });
                })
                ->with([
                    'eligibilityRule',
                    'activeBenefits.configurations',
                ])
                ->get();

            $recommendations = $programs
                ->flatMap(function ($program) use ($farmer, $eligibilityService, $now) {
                    $normalAvailability = $program->is_active
                        && $program->start_at
                        && $program->end_at
                        && $program->start_at->lte($now)
                        && $program->end_at->gte($now);

                    return $program->activeBenefits
                        ->filter(function ($benefit) use ($normalAvailability, $program, $now) {
                            if ($program->isPcicCropInsurance()) {
                                return true;
                            }

                            if ($benefit->eligibility_configured_at === null) {
                                return false;
                            }

                            if ($benefit->always_active) {
                                return true;
                            }

                            if ($benefit->configurations->isNotEmpty()) {
                                return $benefit->configurations->contains(
                                    fn ($configuration) =>
                                        $configuration->start_at->lte($now)
                                        && $configuration->end_at->gte($now)
                                );
                            }

                            // Preserve existing behavior for legacy benefits
                            // without a configuration record.
                            return $normalAvailability;
                        })
                        ->map(function ($benefit) use ($program, $farmer, $eligibilityService) {
                            $evaluation = $eligibilityService->evaluate(
                                $farmer,
                                $program,
                                $benefit
                            );

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
