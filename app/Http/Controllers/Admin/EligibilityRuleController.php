<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\EligibilityRule;
use App\Models\Program;
use App\Services\RecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class EligibilityRuleController extends Controller
{
    public function index(): View
    {
        return view('admin.eligibility', [
            'programs' => Program::with('eligibilityRule')->orderBy('name')->get(),
            'cropTypes' => ['Rice', 'Corn', 'Vegetables', 'Fruits', 'Coconut', 'Other'],
        ]);
    }

    public function store(Request $request, RecommendationService $recommendationService): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'crop_types' => ['nullable', 'array'],
            'crop_types.*' => ['string', 'max:100'],
            'min_land_size' => ['nullable', 'numeric', 'min:0'],
            'max_land_size' => ['nullable', 'numeric', 'min:0'],
            'requires_rsbsa' => ['sometimes', 'boolean'],
            'requires_association' => ['sometimes', 'boolean'],
            'requires_4ps' => ['sometimes', 'boolean'],
        ]);

        $minimum = $validated['min_land_size'] ?? null;
        $maximum = $validated['max_land_size'] ?? null;
        if ($minimum !== null && $maximum !== null && (float) $maximum < (float) $minimum) {
            throw ValidationException::withMessages([
                'max_land_size' => 'The maximum land size must be greater than or equal to the minimum.',
            ]);
        }

        $program = Program::findOrFail($validated['program_id']);
        if ($program->archived_at) {
            throw ValidationException::withMessages([
                'program_id' => 'Eligibility rules cannot be changed for an archived program.',
            ]);
        }

        $attributes = [
            'crop_types' => ! empty($validated['crop_types'])
                ? collect($validated['crop_types'])
                    ->map(fn ($crop) => trim($crop))
                    ->filter()
                    ->unique(fn ($crop) => strtolower($crop))
                    ->values()
                    ->all()
                : null,
            'min_land_size' => $minimum,
            'max_land_size' => $maximum,
            'requires_rsbsa' => $request->boolean('requires_rsbsa'),
            'requires_association' => $request->boolean('requires_association'),
            'requires_4ps' => $request->boolean('requires_4ps'),
        ];

        DB::transaction(function () use ($program, $attributes, $request) {
            $rules = $program->eligibilityRules()
                ->whereNull('program_benefit_id')
                ->lockForUpdate()
                ->get();

            if ($rules->count() > 1) {
                throw ValidationException::withMessages([
                    'program_id' => 'Multiple program-wide rules exist. Resolve the duplicate rules before updating.',
                ]);
            }

            $rule = $rules->first();
            if ($rule) {
                $rule->update($attributes);
            } else {
                $rule = $program->eligibilityRules()->create($attributes + [
                    'program_benefit_id' => null,
                ]);
            }

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'model' => 'EligibilityRule',
                'model_id' => $rule?->id,
                'details' => [
                    'scope' => 'program',
                    'program_id' => $program->id,
                    'attributes' => $attributes,
                ],
            ]);
        });

        $recommendationService->refreshAll();

        return back()->with('success', 'Program-wide eligibility rules saved successfully.');
    }

    public function destroy(
        EligibilityRule $eligibilityRule,
        RecommendationService $recommendationService
    ): RedirectResponse {
        if ($eligibilityRule->program_benefit_id !== null) {
            abort(404);
        }

        DB::transaction(function () use ($eligibilityRule) {
            $ruleId = $eligibilityRule->id;
            $programId = $eligibilityRule->program_id;
            $eligibilityRule->delete();

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'deleted',
                'model' => 'EligibilityRule',
                'model_id' => $ruleId,
                'details' => [
                    'scope' => 'program',
                    'program_id' => $programId,
                ],
            ]);
        });

        $recommendationService->refreshAll();

        return back()->with('success', 'Program-wide eligibility rule deleted.');
    }
}
