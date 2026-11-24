<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\AuditLogService;
use App\Services\EligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Application::where(fn ($query) => $query->withCurrentBenefit()->orWhereHas(
            'program',
            fn ($program) => $program->where('name', 'PCIC Crop Insurance')
        ))->with([
            'farmer.user',
            'program',
            'benefit',
            'detail',
            'pcicDamageIncident',
        ])->latest('applied_at');

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('farmer', function ($farmerQuery) use ($search) {
                    $farmerQuery->whereLike('full_name', "%{$search}%")
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->whereLike('email', "%{$search}%");
                        });
                })
                ->orWhereHas('program', function ($programQuery) use ($search) {
                    $programQuery->whereLike('name', "%{$search}%");
                })
                ->orWhereHas('benefit', function ($benefitQuery) use ($search) {
                    $benefitQuery->whereLike('name', "%{$search}%");
                });
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $applications = $query->paginate(10)->withQueryString();

        return view('admin.applications', compact('applications'));
    }

    /**
     * Display a farmer's submitted application details.
     */
    public function show(Application $application): View
    {
        $application->load([
            'farmer.user',
            'program',
            'benefit',
            'detail',
            'pcicDamageIncident',
        ]);

        return view('admin.applications.show', [
            'application' => $application,
        ]);
    }

    public function approve(
        Application $application,
        Request $request,
        EligibilityService $eligibilityService,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $result = DB::transaction(function () use ($application, $request, $eligibilityService, $auditLogService) {
            $locked = Application::query()
                ->with(['farmer', 'program', 'benefit'])
                ->whereKey($application->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'pending') {
                return 'not_pending';
            }

            if (! $locked->farmer || ! $locked->program) {
                return 'missing_history';
            }

            $isPcicClaim = $locked->isPcicClaim();

            if (! $isPcicClaim && $eligibilityService->evaluate(
                $locked->farmer, $locked->program, $locked->benefit
            )['status'] !== 'eligible') {
                return 'ineligible';
            }

            $locked->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'remarks' => 'Application approved by admin.',
            ]);

            if ($locked->program?->isPcicCropInsurance()) {
                $locked->pcicTrackingUpdates()->create([
                    'user_id' => $request->user()->id,
                    'status' => $isPcicClaim ? 'claim_approved_by_fams' : 'application_approved',
                    'remarks' => $locked->remarks,
                ]);
            }

            $auditLogService->log(
                'APPLICATION_APPROVED',
                $locked,
                'Application approved for benefit: ' . ($locked->benefit?->name ?? 'Legacy program-level application')
            );

            return 'approved';
        });

        if ($result !== 'approved') {
            $message = match ($result) {
                'not_pending' => 'Only pending applications can be approved.',
                'missing_history' => 'The application is missing its farmer or program record.',
                default => 'The farmer does not currently meet the application eligibility rules.',
            };

            return back()->with('error', $message);
        }

        return back()->with('success', 'Farmer application approved successfully.');
    }

    public function reject(
        Application $application,
        Request $request,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $validated = $request->validateWithBag('applicationRejection', [
            'remarks' => ['required', 'string', 'max:2000'],
        ]);

        $rejected = DB::transaction(function () use (
            $application,
            $request,
            $validated,
            $auditLogService
        ) {
            $locked = Application::query()
                ->with(['program', 'benefit'])
                ->whereKey($application->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'pending') {
                return false;
            }

            $locked->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'remarks' => $validated['remarks'],
            ]);

            if ($locked->program?->isPcicCropInsurance()) {
                $locked->pcicTrackingUpdates()->create([
                    'user_id' => $request->user()->id,
                    'status' => 'application_rejected',
                    'remarks' => $locked->remarks,
                ]);
            }

            $auditLogService->log(
                'APPLICATION_REJECTED',
                $locked,
                'Application rejected for benefit: ' . ($locked->benefit?->name ?? 'Legacy program-level application')
            );

            return true;
        });

        return $rejected
            ? back()->with('success', 'Farmer application rejected successfully.')
            : back()->with('error', 'Only pending applications can be rejected.');
    }
}
