<?php

namespace App\Http\Requests;

use App\Models\ProgramBenefit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProgramApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {

    $program = $this->route('program');
    $programId = $program instanceof \App\Models\Program
        ? $program->getKey()
        : $program;

    $rules = [
        'program_benefit_id' => [
            'required',
            'integer',
            Rule::exists('program_benefits', 'id')
                ->where('program_id', $programId),
        ],
    ];


        $benefit = ProgramBenefit::with('program')
            ->where('program_id', $programId)
            ->find($this->input('program_benefit_id'));

        if (! $benefit || ! $benefit->program) {
            return $rules;
        }

        $programName = $benefit->program->name;
        $benefitName = $benefit->name;

        if ($programName === 'PCIC Crop Insurance') {
            $rules = array_merge(
                $rules,
                $this->pcicRules($benefitName)
            );
        } elseif ($programName === 'Agricultural Loans') {
            $rules = array_merge(
                $rules,
                $this->loanRules()
            );

        } elseif (in_array($programName, [
            'Seed Subsidies',
            'Fertilizer Subsidies',
        ], true)) {
            $rules['confirm_intent'] = ['required', 'accepted'];
        } elseif (in_array($programName, [
            'Equipment and Machinery Support',
            'Post-Harvest Support',
            'Marketing Support',
            'Training and Technology Demonstrations',
            'Infrastructure Support',
        ], true)) {
            $rules = array_merge(
                $rules,
                $this->serviceRequestRules()
            );
        }

        return $rules;

    }

    private function pcicRules(string $benefitName): array
    {
        $rules = [
            'variety_planted' => [
                'required',
                Rule::in(['Inbred', 'Hybrid']),
            ],
            'actual_planting_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'expected_harvest_date' => [
                'required',
                'date',
                'after:actual_planting_date',
            ],
            'adjacent_north' => ['required', 'string', 'max:255'],
            'adjacent_south' => ['required', 'string', 'max:255'],
            'adjacent_east' => ['required', 'string', 'max:255'],
            'adjacent_west' => ['required', 'string', 'max:255'],
        ];

        if ($benefitName === 'New Application') {
            $areaRules = ['required', 'numeric', 'min:0.01', 'max:10000'];

            if (array_key_exists('lots', $this->all())) {
                $rules['lots'] = ['required', 'array', 'list', 'min:1', 'max:3'];
                $rules['lots.*'] = ['required', 'array:area_to_insure,adjacent_north,adjacent_south,adjacent_east,adjacent_west'];
                $rules['lots.*.area_to_insure'] = $areaRules;

                foreach (['north', 'south', 'east', 'west'] as $direction) {
                    $field = 'adjacent_'.$direction;
                    $rules['lots.*.'.$field] = $rules[$field];
                    unset($rules[$field]);
                }
            } else {
                $rules['area_to_insure'] = $areaRules;
            }
        }

        if ($benefitName === 'Claim for Indemnity') {
            $rules = [];

            $rules['date_of_loss'] = [
                'required',
                'date',
                'before_or_equal:today',
            ];

            $rules['cause_of_loss'] = [
                'required',
                Rule::in([
                    'Typhoon',
                    'Flood',
                    'Drought',
                    'Pest',
                ]),
            ];

            $rules['area_damaged'] = [
                'required',
                'numeric',
                'gt:0',
                'max:10000',
            ];

            $rules['damage_photo'] = [
                'required',
                'image',
                'mimes:jpeg,png,webp',
                'max:5120',
            ];
        }

        return $rules;
    }

    private function loanRules(): array
    {
        return [
            'loan_amount' => [
                'required',
                'numeric',
                'gt:0',
                'max:100000000',
            ],
            'loan_purpose' => [
                'required',
                'string',
                'max:1000',
            ],
            'project_location' => [
                'required',
                'string',
                'max:255',
            ],
            'number_of_hectares' => [
                'required',
                'numeric',
                'gt:0',
                'max:10000',
            ],
            'project_description' => [
                'required',
                'string',
                'max:2000',
            ],
            'agribusiness_entity' => [
                'required',
                'string',
                'max:255',
            ],
            'sworn_statement' => [
                'required',
                'accepted',
            ],
            'privacy_consent' => [
                'required',
                'accepted',
            ],
        ];
    }


    private function serviceRequestRules(): array
    {
        return [
            'service_description' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'preferred_service_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

}
