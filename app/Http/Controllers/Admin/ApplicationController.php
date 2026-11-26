<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Services\AuditLogService;
use App\Services\EligibilityService;
use App\Services\FarmerDecisionMailer;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

use function Illuminate\Support\defer;

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
            'pcicDecisionAttachment',
        ]);

        return view('admin.applications.show', [
            'application' => $application,
        ]);
    }

    public function downloadSubmittedForm(Application $application): Response
    {
        $application->load(['farmer', 'program', 'benefit', 'detail', 'pcicDamageIncident']);
        abort_unless($application->hasPcicSubmittedForm(), 404);

        return Pdf::loadView('admin.applications.pcic-submitted-form', ['application' => $application])
            ->setPaper('a4')
            ->download("pcic-application-{$application->id}.pdf");
    }

    public function approve(
        Application $application,
        Request $request,
        EligibilityService $eligibilityService,
        AuditLogService $auditLogService
    ): RedirectResponse {
        return $this->review($application, $request, $auditLogService, $eligibilityService);
    }

    public function reject(
        Application $application,
        Request $request,
        AuditLogService $auditLogService
    ): RedirectResponse {
        return $this->review($application, $request, $auditLogService);
    }

    private function review(
        Application $application,
        Request $request,
        AuditLogService $auditLogService,
        ?EligibilityService $eligibilityService = null
    ): RedirectResponse {
        $approving = $eligibilityService !== null;
        $status = $approving ? 'approved' : 'rejected';
        $requiresAttachment = $application->requiresDecisionAttachment();
        $rules = $approving ? [] : ['remarks' => ['required', 'string', 'max:2000']];

        if ($requiresAttachment) {
            $rules['decision_attachment'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];
        }
        $validated = $request->validateWithBag('applicationRejection', $rules);
        $path = null;

        try {
            $result = DB::transaction(function () use (
                $application, $request, $auditLogService, $eligibilityService,
                $approving, $status, $validated, &$path
            ) {
                $locked = Application::with(['farmer', 'program', 'benefit'])
                    ->lockForUpdate()
                    ->findOrFail($application->id);

                if ($locked->status !== 'pending') {
                    return "Only pending applications can be {$status}.";
                }

                if ($approving && (! $locked->farmer || ! $locked->program)) {
                    return 'The application is missing its farmer or program record.';
                }

                $isPcicClaim = $locked->isPcicClaim();
                if ($eligibilityService && ! $isPcicClaim && $eligibilityService->evaluate(
                    $locked->farmer, $locked->program, $locked->benefit
                )['status'] !== 'eligible') {
                    return 'The farmer does not currently meet the application eligibility rules.';
                }

                $isPcic = $locked->program?->isPcicCropInsurance();
                if ($locked->requiresDecisionAttachment()) {
                    $directory = $isPcic ? 'pcic-decisions' : 'loan-decisions';
                    $path = $validated['decision_attachment']->store("{$directory}/{$locked->id}", 'private');
                    if (! $path) {
                        return 'The decision attachment could not be saved. Please try again.';
                    }
                }

                $locked->update([
                    'loan_decision_attachment_path' => $locked->isAgriculturalLoan() ? $path : null,
                    'status' => $status,
                    'reviewed_at' => now(),
                    'remarks' => $validated['remarks'] ?? 'Application approved by admin.',
                ]);

                if ($isPcic) {
                    $locked->pcicTrackingUpdates()->create([
                        'user_id' => $request->user()->id,
                        'status' => match (true) {
                            ! $approving => 'application_rejected',
                            $isPcicClaim => 'claim_approved_by_fams',
                            default => 'application_approved',
                        },
                        'remarks' => $locked->remarks,
                        'attachment_path' => $path,
                    ]);
                }

                $auditLogService->log(
                    'APPLICATION_' . strtoupper($status),
                    $locked,
                    'Application ' . $status . ' for benefit: ' . ($locked->benefit?->name ?? 'Legacy program-level application')
                );

                return $locked;
            });
        } catch (\Throwable $exception) {
            if ($path) {
                Storage::disk('private')->delete($path);
            }
            throw $exception;
        }

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        defer(fn () => app(FarmerDecisionMailer::class)->application($result));

        return back()->with('success', "Farmer application {$status} successfully.");
    }
}
