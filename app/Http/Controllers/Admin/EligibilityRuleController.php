<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EligibilityRule;
use App\Models\Program;
use App\Services\RecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EligibilityRuleController extends Controller
{
    public function index(): View
    {
        return view('admin.eligibility', [
            'programs' => Program::with('eligibilityRule')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, RecommendationService $recommendationService): RedirectResponse
    {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',
            'crop_types' => 'nullable|array',
            'crop_types.*' => 'string',
            'crop_types_input' => 'nullable|string',
            'min_land_size' => 'nullable|numeric|min:0',
            'max_land_size' => 'nullable|numeric|min:0',
            'requires_rsbsa' => 'boolean',
            'requires_association' => 'boolean',
            'requires_4ps' => 'boolean',
        ]);

        if ($request->filled('crop_types_input')) {
            $validated['crop_types'] = array_map('trim', explode(',', $request->crop_types_input));
        }

        unset($validated['crop_types_input']);

        $validated['requires_rsbsa'] = $request->boolean('requires_rsbsa');
        $validated['requires_association'] = $request->boolean('requires_association');
        $validated['requires_4ps'] = $request->boolean('requires_4ps');

        EligibilityRule::updateOrCreate(
            ['program_id' => $validated['program_id']],
            $validated
        );

        $recommendationService->refreshAll();

        return back()->with('success', 'Eligibility rules saved successfully.');
    }

    public function destroy(EligibilityRule $eligibilityRule, RecommendationService $recommendationService): RedirectResponse
    {
        $eligibilityRule->delete();
        $recommendationService->refreshAll();

        return back()->with('success', 'Eligibility rule deleted.');
    }
}
