<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\PcicTrackingUpdate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PcicTrackingController extends Controller
{
    public function store(Request $request, Application $application): RedirectResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless($application->program?->isPcicCropInsurance(), 404);

        $validated = $request->validate([
            'status' => ['required', Rule::in(PcicTrackingUpdate::ADMIN_STATUSES)],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'pcic_reference' => [
                Rule::requiredIf(in_array($request->input('status'), PcicTrackingUpdate::OFFICIAL_STATUSES, true)),
                'nullable', 'string', 'max:255',
            ],
            'expected_release_date' => ['required_if:status,release_scheduled', 'nullable', 'date_format:Y-m-d'],
        ]);

        DB::transaction(function () use ($application, $request, $validated) {
            $locked = Application::lockForUpdate()->findOrFail($application->id);

            if (
                in_array($validated['status'], ['approved', 'release_scheduled', 'released'], true)
                && $locked->benefit?->name === 'Claim for Indemnity'
                && $locked->pcicDamageIncident?->status !== 'verified'
            ) {
                throw ValidationException::withMessages(['status' => 'Verify the damage incident before recording approval or release.']);
            }

            $defaults = ['remarks' => null, 'pcic_reference' => null, 'expected_release_date' => null];
            $update = array_replace($defaults, $validated);
            $latest = $locked->latestPcicTrackingUpdate;

            if (
                $latest?->status === $update['status']
                && $latest->remarks === $update['remarks']
                && $latest->pcic_reference === $update['pcic_reference']
                && $latest->expected_release_date?->toDateString() === $update['expected_release_date']
            ) {
                return;
            }

            $locked->pcicTrackingUpdates()->create($update + ['user_id' => $request->user()->id]);
        });

        return back()->with('success', 'PCIC tracking update saved.');
    }
}
