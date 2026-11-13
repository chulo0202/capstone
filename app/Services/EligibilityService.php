<?php

namespace App\Services;

use App\Models\EligibilityRule;
use App\Models\Farmer;
use App\Models\Program;
use App\Models\ProgramBenefit;

class EligibilityService
{
    public function evaluate(
        Farmer $farmer,
        Program $program,
        ?ProgramBenefit $benefit = null
    ): array {
        $global = $this->evaluateRule($farmer, $program->eligibilityRule);
        $benefitResult = [
            'status' => 'eligible',
            'missing_requirements' => [],
            'met' => 0,
            'total' => 0,
        ];

        if ($benefit) {
            if ((int) $benefit->program_id !== (int) $program->id) {
                $benefitResult = $this->notEligible('Benefit does not belong to this program.');
            } elseif (! $benefit->eligibility_configured_at) {
                $benefitResult = $this->notEligible('Benefit eligibility has not been configured.');
            } else {
                $benefitResult = $this->evaluateRule(
                    $farmer,
                    $benefit->eligibilityRules()->first()
                );
            }
        }

        $missing = array_values(array_merge(
            $global['missing_requirements'],
            $benefitResult['missing_requirements']
        ));

        $status = $this->combinedStatus($global, $benefitResult);

        return [
            'status' => $status,
            'missing_requirements' => $missing,
            'global' => $global,
            'benefit' => $benefitResult,
        ];
    }

    private function evaluateRule(Farmer $farmer, ?EligibilityRule $rule): array
    {
        if (! $rule) {
            return [
                'status' => 'eligible',
                'missing_requirements' => [],
                'met' => 0,
                'total' => 0,
            ];
        }

        $missing = [];

        if ($rule->crop_types) {
            $allowedCrops = collect($rule->crop_types)
                ->map(fn ($crop) => strtolower(trim($crop)))
                ->filter()
                ->values()
                ->all();

            if (! in_array(strtolower(trim($farmer->crop_type ?? '')), $allowedCrops, true)) {
                $missing[] = 'Crop type must be: ' . implode(', ', $rule->crop_types);
            }
        }

        if ($rule->min_land_size !== null && $farmer->land_size < $rule->min_land_size) {
            $missing[] = "Minimum land size: {$rule->min_land_size} hectares";
        }

        if ($rule->max_land_size !== null && $farmer->land_size > $rule->max_land_size) {
            $missing[] = "Maximum land size: {$rule->max_land_size} hectares";
        }

        if ($rule->requires_rsbsa && ! $farmer->rsbsa_status) {
            $missing[] = 'RSBSA registration required';
        }

        if ($rule->requires_association && ! $farmer->association_membership) {
            $missing[] = 'Farmers association membership required';
        }

        if ($rule->requires_4ps && ! $farmer->four_ps_membership) {
            $missing[] = '4Ps beneficiary status required';
        }

        $total = $this->ruleRequirementCount($rule);

        return [
            'status' => $missing ? 'not_eligible' : 'eligible',
            'missing_requirements' => $missing,
            'met' => $total - count($missing),
            'total' => $total,
        ];
    }

    private function combinedStatus(array $global, array $benefit): string
    {
        $total = $global['total'] + $benefit['total'];
        $met = $global['met'] + $benefit['met'];

        if ($total === 0 || $met === $total) {
            return 'eligible';
        }

        return $met > 0 ? 'partially_eligible' : 'not_eligible';
    }

    private function notEligible(string $reason): array
    {
        return [
            'status' => 'not_eligible',
            'missing_requirements' => [$reason],
            'met' => 0,
            'total' => 1,
        ];
    }

    private function ruleRequirementCount(EligibilityRule $rule): int
    {
        return (int) (bool) $rule->crop_types
            + (int) ($rule->min_land_size !== null)
            + (int) ($rule->max_land_size !== null)
            + (int) $rule->requires_rsbsa
            + (int) $rule->requires_association
            + (int) $rule->requires_4ps;
    }
}
