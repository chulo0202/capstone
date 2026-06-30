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

        if ($rule->crop_types) {
            $total++;
            if (in_array($farmer->crop_type, $rule->crop_types, true)) {
                $met++;
            } else {
                $missing[] = 'Crop type must be: '.implode(', ', $rule->crop_types);
            }
        }

        if ($rule->min_land_size !== null) {
            $total++;
            if ($farmer->land_size >= $rule->min_land_size) {
                $met++;
            } else {
                $missing[] = "Minimum land size: {$rule->min_land_size} hectares";
            }
        }

        if ($rule->max_land_size !== null) {
            $total++;
            if ($farmer->land_size <= $rule->max_land_size) {
                $met++;
            } else {
                $missing[] = "Maximum land size: {$rule->max_land_size} hectares";
            }
        }

        if ($rule->requires_rsbsa) {
            $total++;
            if ($farmer->rsbsa_status) {
                $met++;
            } else {
                $missing[] = 'RSBSA registration required';
            }
        }

        if ($rule->requires_association) {
            $total++;
            if ($farmer->association_membership) {
                $met++;
            } else {
                $missing[] = 'Farmers association membership required';
            }
        }

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
