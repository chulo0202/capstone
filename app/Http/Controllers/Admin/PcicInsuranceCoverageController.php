<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Farmer;
use App\Models\PcicInsuranceCoverage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PcicInsuranceCoverageController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,verified,rejected'],
        ]);

        $coverages = PcicInsuranceCoverage::query()
            ->with(['farmer', 'verifier'])
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where(
                    'verification_status',
                    $validated['status']
                )
            )
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pcic-coverages', compact('coverages'));
    }
    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'farmer_id' => [
                'required',
                'integer',
                Rule::exists('farmers', 'id'),
            ],
            'policy_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique(
                    'pcic_insurance_coverages',
                    'policy_number'
                ),
            ],
            'crop_type' => [
                'required',
                'string',
                'max:100',
            ],
            'area_insured' => [
                'required',
                'numeric',
                'gt:0',
                'max:99999999.99',
            ],
            'coverage_start_date' => [
                'required',
                'date',
            ],
            'coverage_end_date' => [
                'required',
                'date',
                'after_or_equal:coverage_start_date',
            ],
        ]);

        DB::transaction(function () use ($request, $validated) {
            Farmer::query()
                ->whereKey($validated['farmer_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $coverage = PcicInsuranceCoverage::create([
                ...$validated,
                'verification_status' => 'pending',
                'verified_by' => null,
                'verified_at' => null,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'PCIC_COVERAGE_RECORDED',
                'model' => 'PcicInsuranceCoverage',
                'model_id' => $coverage->id,
                'details' => [
                    'farmer_id' => $coverage->farmer_id,
                    'policy_number' => $coverage->policy_number,
                ],
            ]);
        });

        return back()->with(
            'success',
            'PCIC insurance record created. Verification is pending.'
        );
    }


    public function verify(
        Request $request,
        PcicInsuranceCoverage $coverage
    ): RedirectResponse {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'verification_confirmed' => [
                'required',
                'accepted',
            ],
            'verification_evidence_reference' => [
                'required',
                'string',
                'min:5',
                'max:255',
            ],
            'verification_notes' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use ($request, $coverage, $validated) {
            $lockedCoverage = PcicInsuranceCoverage::query()
                ->whereKey($coverage->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedCoverage->verification_status !== 'pending') {
                abort(409, 'Only pending coverage records can be verified.');
            }

            if (
                $lockedCoverage->coverage_end_date
                    ->lt($lockedCoverage->coverage_start_date)
                || (float) $lockedCoverage->area_insured <= 0
            ) {
                abort(422, 'Invalid insurance coverage details.');
            }

            $lockedCoverage->update([
                'verification_status' => 'verified',
                'verification_evidence_reference' =>
                    $validated['verification_evidence_reference'],
                'verification_notes' =>
                    $validated['verification_notes'],
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'PCIC_COVERAGE_VERIFIED',
                'model' => 'PcicInsuranceCoverage',
                'model_id' => $lockedCoverage->id,
                'details' => [
                    'farmer_id' => $lockedCoverage->farmer_id,
                    'policy_number' => $lockedCoverage->policy_number,
                    'verification_evidence_reference' =>
                        $validated['verification_evidence_reference'],
                    'verification_notes' =>
                        $validated['verification_notes'],
                ],
            ]);
        });

        return back()->with(
            'success',
            'PCIC insurance coverage internally verified with evidence reference.'
        );
    }


    public function reject(
        Request $request,
        PcicInsuranceCoverage $coverage
    ): RedirectResponse {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'rejection_reason' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use ($request, $coverage, $validated) {
            $lockedCoverage = PcicInsuranceCoverage::query()
                ->whereKey($coverage->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedCoverage->verification_status !== 'pending') {
                abort(409, 'Only pending insurance records can be rejected.');
            }

            $lockedCoverage->update([
                'verification_status' => 'rejected',
                'rejection_reason' => $validated['rejection_reason'],
                'verified_by' => null,
                'verified_at' => null,
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'PCIC_COVERAGE_REJECTED',
                'model' => 'PcicInsuranceCoverage',
                'model_id' => $lockedCoverage->id,
                'details' => [
                    'farmer_id' => $lockedCoverage->farmer_id,
                    'policy_number' => $lockedCoverage->policy_number,
                    'rejection_reason' => $validated['rejection_reason'],
                ],
            ]);
        });

        return back()->with(
            'success',
            'PCIC insurance coverage rejected successfully.'
        );
    }

}
