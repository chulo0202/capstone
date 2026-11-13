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
        $query = Application::with(['farmer.user', 'program', 'benefit'])
            ->latest('applied_at');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('farmer', fn ($farmerQuery) => $farmerQuery->whereLike('full_name', "%{$search}%"))
                    ->orWhereHas('program', fn ($programQuery) => $programQuery->whereLike('name', "%{$search}%"))
                    ->orWhereHas('benefit', fn ($benefitQuery) => $benefitQuery->whereLike('name', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $applications = $query->paginate(10)->withQueryString();

        return view('admin.applications', compact('applications'));
    }

    public function approve(
        Application $application,
        Request $request,
        EligibilityService $eligibilityService,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $result = DB::transaction(function () use ($application, $eligibilityService, $auditLogService) {
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

            $evaluation = $eligibilityService->evaluate(
                $locked->farmer,
                $locked->program,
                $locked->benefit
            );

            if ($evaluation['status'] !== 'eligible') {
                return 'ineligible';
            }

            $locked->update([
                'status' => 'approved',
                'reviewed_at' => now(),
                'remarks' => 'Application approved by admin.',
            ]);

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
        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $rejected = DB::transaction(function () use (
            $application,
            $request,
            $validated,
            $auditLogService
        ) {
            $locked = Application::query()
                ->with('benefit')
                ->whereKey($application->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->status !== 'pending') {
                return false;
            }

            $locked->update([
                'status' => 'rejected',
                'reviewed_at' => now(),
                'remarks' => $validated['remarks'] ?? 'Application rejected by admin.',
            ]);

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
