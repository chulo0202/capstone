<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApplicationController extends Controller
{
    /**
     * Display all farmer applications.
     */
    public function index(Request $request): View
    {
        $query = Application::with(['farmer.user', 'program'])
            ->latest('applied_at');

        // Search by farmer name or program name
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('farmer', function ($farmerQuery) use ($search) {
                    $farmerQuery->whereLike('full_name', "%{$search}%");
                })
                ->orWhereHas('program', function ($programQuery) use ($search) {
                    $programQuery->whereLike('title', "%{$search}%");
                });
            });
        }

        // Filter by application status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $applications = $query->paginate(10)->withQueryString();

        return view('admin.applications', compact('applications'));
    }

    /**
     * Approve a farmer application.
     */
    public function approve(
    Application $application,
    Request $request,
    AuditLogService $auditLogService
    ): RedirectResponse {
        $application->update([
            'status' => 'approved',
            'reviewed_at' => now(),
            'remarks' => 'Application approved by admin.',
        ]);

        $auditLogService->log(
            'APPLICATION_APPROVED',
            $application,
            'Farmer application approved by admin.'
        );

        return back()->with(
            'success',
            'Farmer application approved successfully.'
        );
    }

    /**
     * Reject a farmer application.
     */
    public function reject(
        Application $application,
        Request $request
    ): RedirectResponse {
        $application->update([
            'status' => 'rejected',
            'reviewed_at' => now(),
            'remarks' => 'Application rejected by admin.',
        ]);

        return back()->with(
            'success',
            'Farmer application rejected successfully.'
        );
    }
}