<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\RecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(): View
    {
        return view('admin.programs', [
            'programs' => Program::with('eligibilityRule')->latest()->paginate(10),
        ]);
    }

    public function store(Request $request, RecommendationService $recommendationService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'eligibility_criteria' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        Program::create($validated);
        $recommendationService->refreshAll();

        return back()->with('success', 'Program created successfully.');
    }

    public function update(Request $request, Program $program, RecommendationService $recommendationService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'eligibility_criteria' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $program->update($validated);
        $recommendationService->refreshAll();

        return back()->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program): RedirectResponse
    {
        $program->delete();

        return back()->with('success', 'Program deleted successfully.');
    }

    public function toggle(Program $program, RecommendationService $recommendationService): RedirectResponse
    {
        $program->update(['is_active' => ! $program->is_active]);
        $recommendationService->refreshAll();

        return back()->with('success', 'Program status updated.');
    }
}
