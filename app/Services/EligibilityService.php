<?php

namespace App\Services;

use App\Models\Farmer;
use App\Models\Program;

class EligibilityService
{
    public function evaluate(Farmer $farmer, Program $program): array
    {
        $rule = $program->eligibilityRule;
        $missing = [];
        $met = 0;
        $total = 0;

        if (! $rule) {
            return [
                'status' => 'eligible',
                'missing_requirements' => [],
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Crop Type
        |--------------------------------------------------------------------------
        */
        if ($rule->crop_types) {
            $total++;

            $farmerCrop = strtolower(trim($farmer->crop_type ?? ''));

            $allowedCrops = collect($rule->crop_types)
                ->map(fn ($crop) => strtolower(trim($crop)))
                ->filter()
                ->values()
                ->toArray();

            if (in_array($farmerCrop, $allowedCrops, true)) {
                $met++;
            } else {
                $missing[] = 'Crop type must be: ' . implode(', ', $rule->crop_types);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Minimum Land Size
        |--------------------------------------------------------------------------
        */
        if ($rule->min_land_size !== null) {
            $total++;

            if ($farmer->land_size >= $rule->min_land_size) {
                $met++;
            } else {
                $missing[] = "Minimum land size: {$rule->min_land_size} hectares";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Maximum Land Size
        |--------------------------------------------------------------------------
        */
        if ($rule->max_land_size !== null) {
            $total++;

            if ($farmer->land_size <= $rule->max_land_size) {
                $met++;
            } else {
                $missing[] = "Maximum land size: {$rule->max_land_size} hectares";
            }
        }

        /*
        |--------------------------------------------------------------------------
        | RSBSA
        |--------------------------------------------------------------------------
        */
        if ($rule->requires_rsbsa) {
            $total++;

            if ($farmer->rsbsa_status) {
                $met++;
            } else {
                $missing[] = 'RSBSA registration required';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Farmers Association
        |--------------------------------------------------------------------------
        */
        if ($rule->requires_association) {
            $total++;

            if ($farmer->association_membership) {
                $met++;
            } else {
                $missing[] = 'Farmers association membership required';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4Ps
        |--------------------------------------------------------------------------
        */
        if ($rule->requires_4ps) {
            $total++;

            if ($farmer->four_ps_membership) {
                $met++;
            } else {
                $missing[] = '4Ps beneficiary status required';
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Determine Eligibility Status
        |--------------------------------------------------------------------------
        */
        if ($total === 0) {
            $status = 'eligible';
        } elseif ($met === $total) {
            $status = 'eligible';
        } elseif ($met > 0) {
            $status = 'partially_eligible';
        } else {
            $status = 'not_eligible';
        }

        return [
            'status' => $status,
            'missing_requirements' => $missing,
        ];
    }
}
