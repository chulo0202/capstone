<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\AuditLogService;
use App\Services\EligibilityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DistributionController extends Controller
{
    public function index(): View
    {
        return view('admin.distributions', [
            'distributions' => Distribution::with([
                'farmer',
                'program',
                'benefit',
                'application',
                'releasedBy',
            ])->latest('distributed_at')->paginate(10),
            'programs' => Program::available()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function scan(): View
    {
        return view('admin.distributions-scan');
    }

    public function verify(Request $request, EligibilityService $eligibilityService): View|RedirectResponse
    {
        $request->validate(['qr_data' => 'required|string']);
        $data = json_decode($request->qr_data, true);

        if (! is_array($data) || empty($data['token'])) {
            return back()->with('error', 'Invalid QR code data.');
        }

        $farmer = Farmer::with('user')
            ->where('qr_code_token', $data['token'])
            ->first();

        if (! $farmer) {
            return back()->with('error', 'Farmer verification failed. QR code is invalid.');
        }

        $applications = Application::with(['program', 'benefit'])
            ->where('farmer_id', $farmer->id)
            ->latest('applied_at')
            ->get();

        $programs = $applications->map(function (Application $application) use ($farmer, $eligibilityService) {
            return $this->applicationRow($application, $farmer, $eligibilityService);
        });

        $now = now();
        $availablePrograms = Program::available()
            ->whereNotNull('start_at')
            ->whereNotNull('end_at')
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->whereHas('activeBenefits')
            ->with(['eligibilityRule', 'activeBenefits.eligibilityRules'])
            ->get();

        foreach ($availablePrograms as $program) {
            foreach ($program->activeBenefits as $benefit) {
                $alreadyListed = $applications->contains(fn (Application $application) =>
                    (int) $application->program_id === (int) $program->id
                    && (int) $application->program_benefit_id === (int) $benefit->id
                );

                if ($alreadyListed) {
                    continue;
                }

                $evaluation = $eligibilityService->evaluate($farmer, $program, $benefit);
                $programs->push([
                    'program' => $program,
                    'benefit' => $benefit,
                    'status' => $evaluation['status'],
                    'missing' => $evaluation['missing_requirements'],
                    'application' => null,
                    'application_status' => null,
                    'already_released' => false,
                ]);
            }
        }

        return view('admin.distributions-verify', compact('farmer', 'programs'));
    }

    public function release(
        Request $request,
        EligibilityService $eligibilityService,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $validated = $request->validate([
            'application_id' => ['nullable', 'integer', 'exists:applications,id', 'required_without:farmer_id'],
            'farmer_id' => ['nullable', 'integer', 'exists:farmers,id', 'required_without:application_id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id', 'required_with:farmer_id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $result = DB::transaction(function () use (
            $validated,
            $request,
            $eligibilityService,
            $auditLogService
        ) {
            $applicationQuery = Application::query()
                ->with(['farmer', 'program', 'benefit'])
                ->lockForUpdate();

            if (! empty($validated['application_id'])) {
                $application = $applicationQuery->whereKey($validated['application_id'])->first();
            } else {
                $matches = $applicationQuery
                    ->where('farmer_id', $validated['farmer_id'])
                    ->where('program_id', $validated['program_id'])
                    ->where('status', 'approved')
                    ->get();

                if ($matches->count() !== 1) {
                    return 'application_ambiguous';
                }

                $application = $matches->first();
            }

            if (! $application || ! $application->farmer || ! $application->program) {
                return 'application_missing';
            }

            if ($application->status !== 'approved') {
                return 'application_unapproved';
            }

            if ($application->program_benefit_id === null) {
                return 'legacy_application_not_releasable';
            }


            $evaluation = $eligibilityService->evaluate(
                $application->farmer,
                $application->program,
                $application->benefit
            );

            if ($evaluation['status'] !== 'eligible') {
                return 'ineligible';
            }

            $existing = Distribution::query()
                ->where(function ($query) use ($application) {
                    $query->where('application_id', $application->id)
                        ->orWhere(function ($scope) use ($application) {
                            $scope->where('farmer_id', $application->farmer_id)
                                ->where('program_id', $application->program_id);

                            if ($application->program_benefit_id === null) {
                                $scope->whereNull('program_benefit_id');
                            } else {
                                $scope->where('program_benefit_id', $application->program_benefit_id);
                            }
                        });
                })
                ->exists();

            if ($existing) {
                return 'already_released';
            }

            $distribution = Distribution::create([
                'farmer_id' => $application->farmer_id,
                'program_id' => $application->program_id,
                'program_benefit_id' => $application->program_benefit_id,
                'application_id' => $application->id,
                'released_by' => $request->user()->id,
                'distributed_at' => now(),
                'notes' => $validated['notes'] ?? null,
            ]);

            $auditLogService->log(
                'BENEFIT_RELEASED',
                $distribution,
                "Application {$application->id} released for " .
                    ($application->benefit?->name ?? 'legacy program-level benefit') . '.'
            );

            return 'released';
        });

        $messages = [
            'application_ambiguous' => 'Provide an application ID when more than one approved application matches.',
            'application_missing' => 'Approved application not found for this farmer and program.',
            'application_unapproved' => 'Only approved applications can be released.',
            'legacy_application_not_releasable' => 'Legacy program-level applications cannot authorize benefit releases. The farmer must apply for a specific benefit.',
            'ineligible' => 'Benefit cannot be released because the farmer does not meet the eligibility rules.',
            'already_released' => 'This application or benefit has already been released.',
        ];

        if ($result !== 'released') {
            return back()->with('error', $messages[$result] ?? 'Benefit release failed.');
        }

        return redirect()
            ->route('admin.distributions.index')
            ->with('success', 'Benefit released successfully.');
    }

    private function applicationRow(
        Application $application,
        Farmer $farmer,
        EligibilityService $eligibilityService
    ): array {
        $evaluation = $eligibilityService->evaluate(
            $farmer,
            $application->program,
            $application->benefit
        );

        $released = Distribution::query()
            ->where(function ($query) use ($application) {
                $query->where('application_id', $application->id)
                    ->orWhere(function ($scope) use ($application) {
                        $scope->where('farmer_id', $application->farmer_id)
                            ->where('program_id', $application->program_id);

                        if ($application->program_benefit_id === null) {
                            $scope->whereNull('program_benefit_id');
                        } else {
                            $scope->where('program_benefit_id', $application->program_benefit_id);
                        }
                    });
            })
            ->exists();

        return [
            'program' => $application->program,
            'benefit' => $application->benefit,
            'status' => $evaluation['status'],
            'missing' => $evaluation['missing_requirements'],
            'application' => $application,
            'application_status' => $application->status,
            'already_released' => $released,
        ];
    }
}
