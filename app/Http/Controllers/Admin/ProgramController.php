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

        if ($status === 'upcoming') {

            $query->whereNotNull('start_at')
                ->where('start_at', '>', $now);

        } elseif ($status === 'active') {

            $query->whereNotNull('start_at')
                ->whereNotNull('end_at')
                ->where('start_at', '<=', $now)
                ->where('end_at', '>=', $now);

        } elseif ($status === 'inactive') {

            $query->where(function ($q) use ($now) {

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
        $availablePrograms = Program::with('benefits')
            ->orderBy('name')
            ->get();

        return view('admin.programs.create', [
            'availablePrograms' => $availablePrograms,
        ]);
    }

    public function show(Program $program): View
    {
        $program->load([
            'benefits',
            'benefits.eligibilityRules',
        ]);

        return view('admin.programs.show', [
            'program' => $program,
        ]);
    }

    public function edit(Program $program): View
    {
        $availablePrograms = Program::with('benefits')
            ->orderBy('name')
            ->get();

        $program->load([
            'benefits',
            'benefits.eligibilityRules',
        ]);

        return view('admin.programs.create', [
            'availablePrograms' => $availablePrograms,
            'program' => $program,
            'isEdit' => true,
        ]);
    }

    public function recommendations(): View
    {
        return view('admin.recommendations', [
            'recommendations' => Recommendation::with(['farmer', 'program'])->latest()->paginate(15),
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


        /*
        |--------------------------------------------------------------------------
        | Get Selected Program
        |--------------------------------------------------------------------------
        */

        $program = Program::findOrFail(
            $validated['program_id']
        );


        /*
        |--------------------------------------------------------------------------
        | Get Selected Benefit
        |--------------------------------------------------------------------------
        |
        | Make sure the selected benefit actually belongs
        | to the selected program.
        |
        */

        $benefit = $program->benefits()
            ->whereKey($validated['program_benefit_id'])
            ->firstOrFail();


        /*
        |--------------------------------------------------------------------------
        | Update Program
        |--------------------------------------------------------------------------
        */

        $program->update([

            'description' => $validated['description'],

            'start_at' => $validated['start_at'],

            'end_at' => $validated['end_at'],

        ]);


        /*
        |--------------------------------------------------------------------------
        | Save Eligibility Rule
        |--------------------------------------------------------------------------
        |
        | One eligibility rule belongs to one specific
        | program + benefit combination.
        |
        */

        $eligibility = $validated['eligibility'] ?? [];


        $program->eligibilityRules()->updateOrCreate(

            [
                'program_benefit_id' => $benefit->id,
            ],

            [

                'crop_types' =>
                    $eligibility['crop_types'] ?? null,

                'min_land_size' =>
                    $eligibility['min_land_size'] ?? null,

                'max_land_size' =>
                    $eligibility['max_land_size'] ?? null,

                'requires_rsbsa' =>
                    $request->boolean(
                        'eligibility.requires_rsbsa'
                    ),

                'requires_association' =>
                    $request->boolean(
                        'eligibility.requires_association'
                    ),

                'requires_4ps' =>
                    $request->boolean(
                        'eligibility.requires_4ps'
                    ),

            ]

        );


        /*
        |--------------------------------------------------------------------------
        | Audit Log
        |--------------------------------------------------------------------------
        */

        AuditLog::create([

            'user_id' =>
                $request->user()->id,

            'action' =>
                'created',

            'model' =>
                'Program',

            'model_id' =>
                $program->id,

            'details' =>
                $validated,

        ]);


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

    public function update(Request $request, Program $program, RecommendationService $recommendationService): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'eligibility_criteria' => 'nullable|string',
            'is_active' => 'boolean',
            'start_at' => 'nullable|date',
            'end_at' => 'nullable|date|after_or_equal:start_at',

            'benefits' => 'nullable|array',
            'benefits.*.name' => 'required|string|max:255',
            'benefits.*.description' => 'nullable|string',

            'eligibility' => 'nullable|array',
            'eligibility.program_benefit_id' => 'nullable|integer',
            'eligibility.crop_types' => 'nullable|array',
            'eligibility.crop_types.*' => 'string',
            'eligibility.min_land_size' => 'nullable|numeric|min:0',
            'eligibility.max_land_size' => 'nullable|numeric|min:0',
            'eligibility.requires_rsbsa' => 'boolean',
            'eligibility.requires_association' => 'boolean',
            'eligibility.requires_4ps' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $program->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'eligibility_criteria' => $validated['eligibility_criteria'] ?? null,
            'is_active' => $validated['is_active'],
            'start_at' => $validated['start_at'] ?? null,
            'end_at' => $validated['end_at'] ?? null,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Update Benefits
        |--------------------------------------------------------------------------
        */
        $program->benefits()->delete();

        foreach ($validated['benefits'] ?? [] as $benefitData) {
            $program->benefits()->create([
                'name' => $benefitData['name'],
                'description' => $benefitData['description'] ?? null,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Update Eligibility Rule
        |--------------------------------------------------------------------------
        */
        $program->eligibilityRule()->delete();

        if (! empty($validated['eligibility'])) {
            $eligibility = $validated['eligibility'];

            $benefitId = $eligibility['program_benefit_id'] ?? null;

            if ($benefitId) {
                $program->eligibilityRule()->create([
                    'program_benefit_id' => $benefitId,
                    'crop_types' => $eligibility['crop_types'] ?? null,
                    'min_land_size' => $eligibility['min_land_size'] ?? null,
                    'max_land_size' => $eligibility['max_land_size'] ?? null,
                    'requires_rsbsa' => $request->boolean('eligibility.requires_rsbsa'),
                    'requires_association' => $request->boolean('eligibility.requires_association'),
                    'requires_4ps' => $request->boolean('eligibility.requires_4ps'),
                ]);
            }
        }

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
