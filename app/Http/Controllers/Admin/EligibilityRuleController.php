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
    $cropTypes = [
        'Rice',
        'Corn',
        'Vegetables',
        'Fruits',
        'Coconut',
        'Other',
    ];

    return view('admin.eligibility', [
        'programs' => Program::with('eligibilityRule')
            ->orderBy('name')
            ->get(),

        'cropTypes' => $cropTypes,
    ]);
}

    public function store(
        Request $request,
        RecommendationService $recommendationService
    ): RedirectResponse {
        $validated = $request->validate([
            'program_id' => 'required|exists:programs,id',

            'crop_types' => 'nullable|array',
            'crop_types.*' => 'string|max:100',

            'min_land_size' => 'nullable|numeric|min:0',
            'max_land_size' => 'nullable|numeric|min:0',

            'requires_rsbsa' => 'boolean',
            'requires_association' => 'boolean',
            'requires_4ps' => 'boolean',
        ]);

        $validated['requires_rsbsa'] = $request->boolean('requires_rsbsa');
        $validated['requires_association'] = $request->boolean('requires_association');
        $validated['requires_4ps'] = $request->boolean('requires_4ps');

        // Clean and remove duplicate crop types
        if (! empty($validated['crop_types'])) {
            $validated['crop_types'] = collect($validated['crop_types'])
                ->map(fn ($crop) => trim($crop))
                ->filter()
                ->unique(fn ($crop) => strtolower($crop))
                ->values()
                ->toArray();
        } else {
            $validated['crop_types'] = null;
        }

        EligibilityRule::updateOrCreate(
            ['program_id' => $validated['program_id']],
            $validated
        );

        $recommendationService->refreshAll();

        return back()->with(
            'success',
            'Eligibility rules saved successfully.'
        );
    }

    public function destroy(
        EligibilityRule $eligibilityRule,
        RecommendationService $recommendationService
    ): RedirectResponse {
        $eligibilityRule->delete();

        $recommendationService->refreshAll();

        return back()->with(
            'success',
            'Eligibility rule deleted.'
        );
    }
}