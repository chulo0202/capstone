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
        $programs = Program::where('is_active', true)->with('eligibilityRule')->get();

        foreach ($programs as $program) {
            $result = $this->eligibilityService->evaluate($farmer, $program);

            Recommendation::updateOrCreate(
                [
                    'farmer_id' => $farmer->id,
                    'program_id' => $program->id,
                ],
                [
                    'eligibility_status' => $result['status'],
                    'missing_requirements' => $result['missing_requirements'],
                ]
            );
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
