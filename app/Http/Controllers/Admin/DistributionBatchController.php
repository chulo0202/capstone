<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\DistributionAssignment;
use App\Models\DistributionBatch;
use App\Models\Program;
use App\Services\AuditLogService;
use App\Services\DistributionReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DistributionBatchController extends Controller
{
    public function index(): View
    {
        return view('admin.distribution-batches.index', [
            'batches' => DistributionBatch::with(['program', 'benefit'])->latest('id')->paginate(15),
        ]);
    }

    public function create(Request $request, DistributionReleaseService $releases): View
    {
        $programs = Program::with('activeBenefits')
            ->whereNull('archived_at')
            ->whereHas('activeBenefits')
            ->orderBy('name')
            ->get();
        $program = $programs->find($request->integer('program'));
        $benefit = $program?->activeBenefits->firstWhere('id', $request->integer('benefit'));

        $applications = $benefit
            ? Application::with(['farmer', 'program', 'benefit'])
                ->where('status', 'approved')
                ->where('program_id', $program->id)
                ->where('program_benefit_id', $benefit->id)
                ->whereNotIn('id', DistributionAssignment::whereNotNull('active_application_id')->select('active_application_id'))
                ->orderBy('id')->get()
                ->filter(fn ($application) => $application->farmer && ! $releases->applicationError($application, $application->farmer))
            : collect();

        return view('admin.distribution-batches.form', [
            'programs' => $programs,
            'program' => $program,
            'benefit' => $benefit,
            'applications' => $applications,
            'batch' => null,
        ]);
    }

    public function show(DistributionBatch $batch): View
    {
        $batch->load(['program', 'benefit', 'assignments.application.farmer']);

        return view('admin.distribution-batches.show', compact('batch'));
    }

    public function edit(DistributionBatch $batch): View
    {
        abort_unless($batch->status === 'draft', 409, 'Only draft schedules can be edited.');

        return view('admin.distribution-batches.form', compact('batch'));
    }

    public function store(Request $request, DistributionReleaseService $releases, AuditLogService $audit): RedirectResponse
    {
        $data = $request->validate($this->scheduleRules() + [
            'program_id' => ['required', 'integer', 'exists:programs,id'],
            'program_benefit_id' => ['required', 'integer', Rule::exists('program_benefits', 'id')->where('program_id', $request->integer('program_id'))],
            'application_ids' => ['required', 'array', 'min:1', 'max:200'],
            'application_ids.*' => ['required', 'integer', 'distinct', 'exists:applications,id'],
        ], [
            'application_ids.required' => 'Please select at least one approved beneficiary.',
            'end_time.after' => 'End time must be later than start time.',
            'scheduled_date.after_or_equal' => 'The distribution date cannot be in the past.',
        ]);
        $ids = $data['application_ids'];
        unset($data['application_ids']);

        $batch = DB::transaction(function () use ($ids, $data, $request, $releases, $audit) {
            $applications = Application::with(['program', 'benefit', 'farmer'])
                ->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $scopes = [];

            if ($applications->count() !== count($ids)) {
                $this->reject('application_ids', 'One or more applications no longer exist.');
            }

            foreach ($applications as $application) {
                if (
                    (int) $application->program_id !== (int) $data['program_id']
                    || (int) $application->program_benefit_id !== (int) $data['program_benefit_id']
                ) {
                    $this->reject('application_ids', 'Selected beneficiaries must belong to the same specific program benefit.');
                }

                $this->validateBeneficiary($application, $releases);

                $scope = $application->farmer_id;
                if (isset($scopes[$scope])) {
                    $this->reject('application_ids', 'Select only one approved application per farmer for this benefit.');
                }
                $scopes[$scope] = true;

                if (DistributionAssignment::where('active_application_id', $application->id)->exists()) {
                    $this->reject('application_ids', 'A selected application is already assigned to another distribution.');
                }
            }

            $batch = DistributionBatch::create($data + ['created_by' => $request->user()->id]);
            $batch->assignments()->createMany($applications->map(fn ($application) => [
                'application_id' => $application->id,
                'active_application_id' => $application->id,
            ])->all());
            $audit->log('DISTRIBUTION_BATCH_CREATED', $batch);

            return $batch;
        }, 3);

        return to_route('admin.distribution-batches.show', $batch)->with('success', 'Distribution draft created.');
    }

    public function update(Request $request, DistributionBatch $batch, AuditLogService $audit): RedirectResponse
    {
        $data = $request->validate($this->scheduleRules(), [
            'end_time.after' => 'End time must be later than start time.',
            'scheduled_date.after_or_equal' => 'The distribution date cannot be in the past.',
        ]);

        DB::transaction(function () use ($batch, $data, $audit) {
            $locked = DistributionBatch::lockForUpdate()->findOrFail($batch->id);
            abort_unless($locked->status === 'draft', 409, 'Only draft schedules can be edited.');
            $locked->update($data);
            $audit->log('DISTRIBUTION_BATCH_UPDATED', $locked);
        });

        return to_route('admin.distribution-batches.show', $batch)->with('success', 'Draft schedule updated.');
    }

    public function publish(Request $request, DistributionBatch $batch, DistributionReleaseService $releases, AuditLogService $audit): RedirectResponse
    {
        $request->validate(['confirm_publish' => ['accepted']]);

        DB::transaction(function () use ($batch, $releases, $audit) {
            $locked = DistributionBatch::lockForUpdate()->findOrFail($batch->id);
            abort_unless($locked->status === 'draft', 409, 'Only draft batches can be published.');

            if ($locked->scheduled_date->lt(today())) {
                $this->reject('scheduled_date', 'The distribution date cannot be in the past.');
            }

            $assignments = $locked->assignments()->whereNotNull('active_application_id')
                ->with('application.farmer', 'application.benefit', 'application.program')->get();

            if ($assignments->isEmpty()) {
                $this->reject('application_ids', 'Please select at least one approved beneficiary.');
            }

            foreach ($assignments as $assignment) {
                $application = $assignment->application;

                if (
                    (int) $application->program_id !== (int) $locked->program_id
                    || (int) $application->program_benefit_id !== (int) $locked->program_benefit_id
                ) {
                    $this->reject('application_ids', 'Selected beneficiaries must belong to the same specific program benefit.');
                }

                $this->validateBeneficiary($application, $releases);
            }

            $locked->update(['status' => 'published']);
            $audit->log('DISTRIBUTION_BATCH_PUBLISHED', $locked);
        });

        return back()->with('success', 'Distribution schedule published.');
    }

    public function cancel(Request $request, DistributionBatch $batch, AuditLogService $audit): RedirectResponse
    {
        $request->validate(['confirm_cancel' => ['accepted']]);

        DB::transaction(function () use ($batch, $audit) {
            $locked = DistributionBatch::lockForUpdate()->findOrFail($batch->id);
            abort_unless(in_array($locked->status, ['draft', 'published'], true), 409, 'This batch can no longer be cancelled.');

            $locked->assignments()->whereNotNull('active_application_id')->whereNull('released_at')->update([
                'active_application_id' => null,
                'cancelled_at' => now(),
            ]);
            $locked->update(['status' => 'cancelled']);
            $audit->log('DISTRIBUTION_BATCH_CANCELLED', $locked);
        });

        return back()->with('success', 'Distribution cancelled. Unreleased applications can be assigned again.');
    }

    private function validateBeneficiary(Application $application, DistributionReleaseService $releases): void
    {
        $error = $application->farmer
            ? $releases->applicationError($application, $application->farmer)
            : 'application_not_found';

        if ($error) {
            $this->reject('application_ids', $error === 'already_released'
                ? 'A selected application or benefit has already been distributed.'
                : 'A selected application is no longer eligible for distribution.');
        }
    }

    private function scheduleRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'scheduled_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'location' => ['required', 'string', 'max:255'],
            'instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }

    private function reject(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
