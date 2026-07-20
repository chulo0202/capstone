<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Program;
use App\Models\Recommendation;
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

    public function recommendations(): View
    {
        return view('admin.recommendations', [
            'recommendations' => Recommendation::with(['farmer', 'program'])->latest()->paginate(15),
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

        $program = Program::create($validated);
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'model' => 'Program',
            'model_id' => $program->id,
            'details' => $validated,
        ]);
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
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'model' => 'Program',
            'model_id' => $program->id,
            'details' => $validated,
        ]);
        $recommendationService->refreshAll();

        return back()->with('success', 'Program updated successfully.');
    }

    public function destroy(Program $program, Request $request): RedirectResponse
    {
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted',
            'model' => 'Program',
            'model_id' => $program->id,
            'details' => ['name' => $program->name],
        ]);
        $program->delete();

        return back()->with('success', 'Program deleted successfully.');
    }

    public function toggle(Program $program, RecommendationService $recommendationService, Request $request): RedirectResponse
    {
        $program->update(['is_active' => ! $program->is_active]);
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'toggled',
            'model' => 'Program',
            'model_id' => $program->id,
            'details' => ['is_active' => $program->is_active],
        ]);
        $recommendationService->refreshAll();

        return back()->with('success', 'Program status updated.');
    }

    public function archive(Program $program, Request $request): RedirectResponse
    {
        $program->update(['is_active' => false]);
        AuditLog::create([
            'user_id' => $request->user()->id,
            'action' => 'archived',
            'model' => 'Program',
            'model_id' => $program->id,
            'details' => ['name' => $program->name],
        ]);

        return back()->with('success', 'Program archived.');
    }
}
