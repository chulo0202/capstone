<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EligibilityRule;
use App\Models\Program;
use App\Models\Recommendation;
use App\Services\RecommendationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        $now = now();

        $query = Program::with('benefits')
            ->latest();

        $status = $request->input('status');

        /*
        |--------------------------------------------------------------------------
        | Status Filter
        |--------------------------------------------------------------------------
        */

        if ($status === 'archived') {
            $query->whereNotNull('archived_at');
        } elseif ($status === 'upcoming') {

            $query->whereNull('archived_at')
                ->whereNotNull('start_at')
                ->where('start_at', '>', $now);

        } elseif ($status === 'active') {

            $query->whereNull('archived_at')
                ->where('is_active', true)
                ->whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now);

        } elseif ($status === 'inactive') {

            $query->whereNull('archived_at')
                ->where(function ($q) use ($now) {

                $q->where(function ($q) use ($now) {

                    $q->whereNotNull('end_at')
                        ->where('end_at', '<', $now);

                })
                ->orWhereNull('start_at')
                ->orWhereNull('end_at');

            });

        }


        /*
        |--------------------------------------------------------------------------
        | Program Ordering
        |--------------------------------------------------------------------------
        |
        | Active programs always appear first.
        | Upcoming programs appear next.
        | Inactive programs appear last.
        |
        */

        $query->orderByRaw(
            "
            CASE
                WHEN archived_at IS NOT NULL
                THEN 3

                WHEN start_at IS NOT NULL
                    AND end_at IS NOT NULL
                    AND start_at <= ?
                    AND end_at >= ?
                THEN 0

                WHEN start_at IS NOT NULL
                    AND start_at > ?
                THEN 1

                ELSE 2
            END
            ",
            [
                $now,
                $now,
                $now,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | Secondary Ordering
        |--------------------------------------------------------------------------
        */

        $query->orderBy('start_at', 'asc');


        return view('admin.programs', [
            'programs' => $query->paginate(10),
        ]);
    }

    public function create(): View
    {
        $availablePrograms = Program::with('activeBenefits')
            ->orderBy('name')
            ->get();

        return view('admin.programs.create', [
            'availablePrograms' => $availablePrograms,
        ]);
    }

    public function show(Program $program): View
    {
        $program->load([
            'eligibilityRule',
            'benefits',
            'benefits.eligibilityRules',
            'activeBenefits.eligibilityRules',
        ]);

        $this->ensureEligibilityRulesAreUnambiguous($program);

        return view('admin.programs.show', [
            'program' => $program,
        ]);
    }

    public function edit(Program $program): View
    {
        $availablePrograms = Program::with('activeBenefits')
            ->orderBy('name')
            ->get();

        $program->load([
            'benefits',
            'benefits.eligibilityRules',
            'eligibilityRule',
            'activeBenefits.eligibilityRules',
        ]);

        $this->ensureEligibilityRulesAreUnambiguous($program);

        return view('admin.programs.create', [
            'availablePrograms' => $availablePrograms,
            'program' => $program,
            'isEdit' => true,
        ]);
    }

    public function recommendations(): View
    {
        return view('admin.recommendations', [
            'recommendations' => Recommendation::with(['farmer', 'program', 'benefit'])->latest()->paginate(15),
        ]);
    }

    public function store(
    Request $request,
    RecommendationService $recommendationService
    ): RedirectResponse {

        $validated = $request->validate([

            'program_id' => [
                'required',
                'exists:programs,id',
            ],

            'program_benefit_id' => [
                'required',
                'exists:program_benefits,id',
            ],

            'description' => [
                'required',
                'string',
            ],

            'start_at' => [
                'required',
                'date',
                'after:now',
            ],

            'end_at' => [
                'required',
                'date',
                'after_or_equal:start_at',
            ],

            'eligibility' => [
                'nullable',
                'array',
            ],

            'eligibility.crop_types' => [
                'nullable',
                'array',
            ],

            'eligibility.crop_types.*' => [
                'nullable',
                'string',
            ],

            'eligibility.min_land_size' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'eligibility.max_land_size' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'eligibility.requires_rsbsa' => [
                'boolean',
            ],

            'eligibility.requires_association' => [
                'boolean',
            ],

            'eligibility.requires_4ps' => [
                'boolean',
            ],

        ]);

        $this->validateLandSizeRange(
            $validated['eligibility'] ?? [],
            'eligibility'
        );

        /*
        |--------------------------------------------------------------------------
        | Get Selected Program
        |--------------------------------------------------------------------------
        */

        $program = Program::findOrFail(
            $validated['program_id']
        );

        if ($program->archived_at) {
            throw ValidationException::withMessages([
                'program_id' => 'Archived programs cannot be configured or reactivated.',
            ]);
        }


        /*
        |--------------------------------------------------------------------------
        | Get Selected Benefit
        |--------------------------------------------------------------------------
        |
        | Make sure the selected benefit actually belongs
        | to the selected program.
        |
        */

        $benefit = $program->activeBenefits()
            ->whereKey($validated['program_benefit_id'])
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Update Program
        |--------------------------------------------------------------------------
        */

        $eligibility = $validated['eligibility'] ?? [];

        DB::transaction(function () use (
            $program,
            $validated,
            $eligibility,
            $request,
            $benefit
        ) {
            $program->activeBenefits()
                ->whereKey($benefit->id)
                ->lockForUpdate()
                ->firstOrFail();

            $program->update([
                'description' => $validated['description'],
                'start_at' => $validated['start_at'],
                'end_at' => $validated['end_at'],
            ]);

            $ruleQuery = $program->eligibilityRules()
                ->where('program_id', $program->id)
                ->where('program_benefit_id', $benefit->id);
            $rule = $this->singleEligibilityRule($ruleQuery, 'eligibility');
            $attributes = [
                'crop_types' => $eligibility['crop_types'] ?? null,
                'min_land_size' => $eligibility['min_land_size'] ?? null,
                'max_land_size' => $eligibility['max_land_size'] ?? null,
                'requires_rsbsa' => $request->boolean('eligibility.requires_rsbsa'),
                'requires_association' => $request->boolean('eligibility.requires_association'),
                'requires_4ps' => $request->boolean('eligibility.requires_4ps'),
            ];

            if ($rule) {
                $rule->update($attributes);
            } else {
                $program->eligibilityRules()->create(
                    $attributes + ['program_benefit_id' => $benefit->id]
                );
            }

            if (! $benefit->eligibility_configured_at) {
                $benefit->eligibility_configured_at = now();
                $benefit->save();
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'created',
                'model' => 'Program',
                'model_id' => $program->id,
                'details' => $validated,
            ]);
        });


        /*
        |--------------------------------------------------------------------------
        | Refresh Recommendations
        |--------------------------------------------------------------------------
        */

        $recommendationService->refreshAll();


        /*
        |--------------------------------------------------------------------------
        | Success
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route('admin.programs.index')
            ->with(
                'success',
                'Program configuration saved successfully.'
            );
    }

    public function update(
        Request $request,
        Program $program,
        RecommendationService $recommendationService
    ): RedirectResponse
    {
        if ($program->archived_at) {
            return back()->with('error', 'Archived programs cannot be edited or reactivated.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'eligibility_criteria' => 'nullable|string',
            'is_active' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',

            'benefits' => 'sometimes|array',
            'benefits.*.id' => 'nullable|integer|distinct',
            'benefits.*.name' => 'required|string|max:255',
            'benefits.*.description' => 'nullable|string',
            'benefits.*.remove' => 'sometimes|boolean',
            'benefits.*.eligibility_configured' => 'sometimes|boolean',

            'global_eligibility' => 'sometimes|array',
            'global_eligibility.crop_types' => 'nullable|array',
            'global_eligibility.crop_types.*' => 'nullable|string|max:100',
            'global_eligibility.min_land_size' => 'nullable|numeric|min:0',
            'global_eligibility.max_land_size' => 'nullable|numeric|min:0',
            'global_eligibility.requires_rsbsa' => 'sometimes|boolean',
            'global_eligibility.requires_association' => 'sometimes|boolean',
            'global_eligibility.requires_4ps' => 'sometimes|boolean',
            'clear_global_eligibility' => 'sometimes|boolean',

            'benefit_rules' => 'sometimes|array',
            'benefit_rules.*.program_benefit_id' => 'required|integer|distinct',
            'benefit_rules.*.crop_types' => 'nullable|array',
            'benefit_rules.*.crop_types.*' => 'nullable|string|max:100',
            'benefit_rules.*.min_land_size' => 'nullable|numeric|min:0',
            'benefit_rules.*.max_land_size' => 'nullable|numeric|min:0',
            'benefit_rules.*.requires_rsbsa' => 'sometimes|boolean',
            'benefit_rules.*.requires_association' => 'sometimes|boolean',
            'benefit_rules.*.requires_4ps' => 'sometimes|boolean',
            'benefit_rules.*.clear' => 'sometimes|boolean',

        ]);

        $this->validateLandSizeRange(
            $validated['global_eligibility'] ?? [],
            'global_eligibility'
        );

        foreach ($validated['benefit_rules'] ?? [] as $index => $ruleData) {
            $this->validateLandSizeRange(
                $ruleData,
                "benefit_rules.{$index}"
            );
        }

        $submittedBenefitIds = [];
        foreach ($validated['benefits'] ?? [] as $index => $benefitData) {
            if (isset($benefitData['id']) && $benefitData['id'] !== '') {
                $benefitId = (int) $benefitData['id'];

                if (isset($submittedBenefitIds[$benefitId])) {
                    throw ValidationException::withMessages([
                        "benefits.{$index}.id" => 'A benefit may only be submitted once.',
                    ]);
                }

                $submittedBenefitIds[$benefitId] = true;
                $belongsToProgram = $program->activeBenefits()
                    ->whereKey($benefitId)
                    ->exists();

                if (! $belongsToProgram) {
                    throw ValidationException::withMessages([
                        "benefits.{$index}.id" => 'The selected benefit does not belong to this program or is archived.',
                    ]);
                }
            } elseif (! empty($benefitData['remove'])) {
                throw ValidationException::withMessages([
                    "benefits.{$index}.remove" => 'A new benefit cannot be removed in the same submission.',
                ]);
            }
        }

        foreach ($validated['benefit_rules'] ?? [] as $index => $ruleData) {
            $isActiveBenefit = $program->activeBenefits()
                ->whereKey($ruleData['program_benefit_id'])
                ->exists();

            if (! $isActiveBenefit) {
                throw ValidationException::withMessages([
                    "benefit_rules.{$index}.program_benefit_id" => 'The selected benefit is not active or does not belong to this program.',
                ]);
            }
        }

        $validated['is_active'] = $request->boolean('is_active');

        DB::transaction(function () use ($validated, $request, $program) {
            $program->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'eligibility_criteria' => $validated['eligibility_criteria'] ?? null,
                'is_active' => $validated['is_active'],
                'start_at' => $validated['start_at'] ?? null,
                'end_at' => $validated['end_at'] ?? null,
            ]);

            $archivedBenefitIds = [];

            foreach ($validated['benefits'] ?? [] as $benefitIndex => $benefitData) {
                if (isset($benefitData['id']) && $benefitData['id'] !== '') {
                    $benefit = $program->activeBenefits()
                        ->whereKey($benefitData['id'])
                        ->lockForUpdate()
                        ->firstOrFail();

                    if (! empty($benefitData['remove'])) {
                        // Applications and distributions are program-scoped; retain history and rules.
                        $benefit->archived_at = now();
                        $benefit->save();
                        $archivedBenefitIds[] = $benefit->id;

                        continue;
                    }

                    $benefit->update([
                        'name' => $benefitData['name'],
                        'description' => $benefitData['description'] ?? null,
                    ]);

                    if (
                        $request->boolean("benefits.{$benefitIndex}.eligibility_configured")
                        && ! $benefit->eligibility_configured_at
                    ) {
                        $benefit->eligibility_configured_at = now();
                        $benefit->save();
                    }

                    continue;
                }

                $newBenefit = $program->benefits()->create([
                    'name' => $benefitData['name'],
                    'description' => $benefitData['description'] ?? null,
                ]);

                if ($request->boolean("benefits.{$benefitIndex}.eligibility_configured")) {
                    $newBenefit->eligibility_configured_at = now();
                    $newBenefit->save();
                }
            }

            if ($request->boolean('clear_global_eligibility')) {
                $program->eligibilityRules()
                    ->where('program_id', $program->id)
                    ->whereNull('program_benefit_id')
                    ->delete();
            } elseif (isset($validated['global_eligibility'])) {
                $attributes = $this->eligibilityRuleAttributes(
                    $validated['global_eligibility'],
                    $request,
                    'global_eligibility'
                );

                if ($this->hasEligibilityRequirements($attributes)) {
                    $globalRuleQuery = $program->eligibilityRules()
                        ->where('program_id', $program->id)
                        ->whereNull('program_benefit_id');
                    $globalRule = $this->singleEligibilityRule(
                        $globalRuleQuery,
                        'global_eligibility'
                    );

                    if ($globalRule) {
                        $globalRule->update($attributes);
                    } else {
                        $program->eligibilityRules()->create(
                            $attributes + ['program_benefit_id' => null]
                        );
                    }
                }
            }

            foreach ($validated['benefit_rules'] ?? [] as $index => $ruleData) {
                if (in_array((int) $ruleData['program_benefit_id'], $archivedBenefitIds, true)) {
                    continue;
                }

                $activeBenefit = $program->activeBenefits()
                    ->whereKey($ruleData['program_benefit_id'])
                    ->lockForUpdate()
                    ->first();

                if (! $activeBenefit) {
                    throw ValidationException::withMessages([
                        "benefit_rules.{$index}.program_benefit_id" => 'The selected benefit is not active or does not belong to this program.',
                    ]);
                }

                $ruleQuery = $program->eligibilityRules()
                    ->where('program_id', $program->id)
                    ->where('program_benefit_id', $ruleData['program_benefit_id']);

                if (! empty($ruleData['clear'])) {
                    $ruleQuery->delete();

                    continue;
                }

                $attributes = $this->eligibilityRuleAttributes(
                    $ruleData,
                    $request,
                    "benefit_rules.{$index}"
                );

                if (! $this->hasEligibilityRequirements($attributes)) {
                    continue;
                }

                $rule = $this->singleEligibilityRule(
                    $ruleQuery,
                    "benefit_rules.{$index}"
                );
                if ($rule) {
                    $rule->update($attributes);
                } else {
                    $program->eligibilityRules()->create(
                        $attributes + [
                            'program_benefit_id' => $ruleData['program_benefit_id'],
                        ]
                    );
                }
            }

            $auditDetails = $validated;
            $auditDetails['archived_benefit_ids'] = $archivedBenefitIds;

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'model' => 'Program',
                'model_id' => $program->id,
                'details' => $auditDetails,
            ]);
        });

        $recommendationService->refreshAll();

        return back()->with('success', 'Program updated successfully.');
    }

    public function destroy(
        Program $program,
        Request $request,
        RecommendationService $recommendationService
    ): RedirectResponse {
        if ($program->archived_at) {
            return back()->with('info', 'Program is already archived.');
        }

        DB::transaction(function () use ($program, $request) {
            $program->update([
                'is_active' => false,
                'archived_at' => $program->archived_at ?? now(),
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'archived',
                'model' => 'Program',
                'model_id' => $program->id,
                'details' => [
                    'name' => $program->name,
                    'applications_count' => $program->applications()->count(),
                    'distributions_count' => $program->distributions()->count(),
                ],
            ]);
        });

        $recommendationService->refreshAll();

        return back()->with('success', 'Program archived successfully.');
    }

    public function toggle(
        Program $program,
        RecommendationService $recommendationService,
        Request $request
    ): RedirectResponse {
        if ($program->archived_at) {
            return back()->with('error', 'Archived programs cannot be reactivated.');
        }

        DB::transaction(function () use ($program, $request) {
            $program->update(['is_active' => ! $program->is_active]);
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'toggled',
                'model' => 'Program',
                'model_id' => $program->id,
                'details' => ['is_active' => $program->is_active],
            ]);
        });

        $recommendationService->refreshAll();

        return back()->with('success', 'Program status updated.');
    }

    public function archive(
        Program $program,
        Request $request,
        RecommendationService $recommendationService
    ): RedirectResponse {
        if ($program->archived_at) {
            return back()->with('info', 'Program is already archived.');
        }

        DB::transaction(function () use ($program, $request) {
            $program->update([
                'is_active' => false,
                'archived_at' => $program->archived_at ?? now(),
            ]);
            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'archived',
                'model' => 'Program',
                'model_id' => $program->id,
                'details' => [
                    'name' => $program->name,
                    'archived_at' => $program->archived_at,
                ],
            ]);
        });

        $recommendationService->refreshAll();

        return back()->with('success', 'Program archived.');
    }

    private function eligibilityRuleAttributes(
        array $ruleData,
        Request $request,
        string $prefix
    ): array {
        return [
            'crop_types' => ! empty($ruleData['crop_types'])
                ? array_values(array_filter(
                    $ruleData['crop_types'],
                    fn ($crop) => is_string($crop) && trim($crop) !== ''
                ))
                : null,
            'min_land_size' => $ruleData['min_land_size'] ?? null,
            'max_land_size' => $ruleData['max_land_size'] ?? null,
            'requires_rsbsa' => $request->boolean("{$prefix}.requires_rsbsa"),
            'requires_association' => $request->boolean("{$prefix}.requires_association"),
            'requires_4ps' => $request->boolean("{$prefix}.requires_4ps"),
        ];
    }

    private function ensureEligibilityRulesAreUnambiguous(Program $program): void
    {
        $ruleCounts = $program->eligibilityRules()
            ->get()
            ->groupBy(fn (EligibilityRule $rule) => $rule->program_benefit_id === null
                ? 'global'
                : "benefit:{$rule->program_benefit_id}");

        foreach ($ruleCounts as $scope => $rules) {
            if ($rules->count() > 1) {
                abort(
                    409,
                    "Multiple eligibility rules exist for {$scope}. Resolve the duplicate records before viewing or editing this program."
                );
            }
        }
    }

    private function singleEligibilityRule(
        Builder $query,
        string $attribute
    ): ?EligibilityRule {
        $rules = $query->lockForUpdate()->get();

        if ($rules->count() > 1) {
            throw ValidationException::withMessages([
                $attribute => 'Multiple eligibility rules exist for this scope. Resolve the duplicate records before updating it.',
            ]);
        }

        return $rules->first();
    }

    private function hasEligibilityRequirements(array $attributes): bool
    {
        return ! empty($attributes['crop_types'])
            || $attributes['min_land_size'] !== null
            || $attributes['max_land_size'] !== null
            || $attributes['requires_rsbsa']
            || $attributes['requires_association']
            || $attributes['requires_4ps'];
    }

    private function validateLandSizeRange(array $ruleData, string $prefix): void
    {
        $minimum = $ruleData['min_land_size'] ?? null;
        $maximum = $ruleData['max_land_size'] ?? null;

        if (
            $minimum !== null
            && $maximum !== null
            && (float) $maximum < (float) $minimum
        ) {
            throw ValidationException::withMessages([
                "{$prefix}.max_land_size" => 'The maximum land size must be greater than or equal to the minimum land size.',
            ]);
        }
    }
}
