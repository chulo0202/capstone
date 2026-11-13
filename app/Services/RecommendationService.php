<?php

namespace App\Services;

use App\Models\Farmer;
use App\Models\Program;
use App\Models\Recommendation;

class RecommendationService
{
    public function __construct(
        protected EligibilityService $eligibilityService
    ) {}

    public function generateForFarmer(Farmer $farmer): void
    {
        $now = now();
        $programs = Program::available()
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->with(['eligibilityRule', 'activeBenefits'])
            ->get();

        foreach ($programs as $program) {
            foreach ($program->activeBenefits as $benefit) {
                if (! $benefit->eligibility_configured_at) {
                    continue;
                }

                $result = $this->eligibilityService->evaluate($farmer, $program, $benefit);

                Recommendation::updateOrCreate(
                    [
                        'farmer_id' => $farmer->id,
                        'program_id' => $program->id,
                        'program_benefit_id' => $benefit->id,
                    ],
                    [
                        'eligibility_status' => $result['status'],
                        'missing_requirements' => $result['missing_requirements'],
                    ]
                );
            }
        }
    }

    public function refreshAll(): void
    {
        Farmer::where('profile_completed', true)->chunk(50, function ($farmers) {
            foreach ($farmers as $farmer) {
                $this->generateForFarmer($farmer);
            }
        });
    }
}
