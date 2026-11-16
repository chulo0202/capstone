<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\AuditLogService;
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

    public function verify(Request $request): View|RedirectResponse
    {
        $validated = $request->validate([
            'qr_data' => ['required', 'string'],
        ]);

        $decoded = json_decode($validated['qr_data'], true);

        $token = is_array($decoded)
            ? ($decoded['token'] ?? null)
            : $validated['qr_data'];

        if (! is_string($token) || $token === '') {
            return redirect()
                ->route('admin.distributions.index')
                ->with('error', 'Invalid QR code.');
        }

        $farmer = Farmer::where('qr_code_token', $token)->first();

        if (! $farmer) {
            return redirect()
                ->route('admin.distributions.index')
                ->with('error', 'Farmer not found.');
        }

        $applications = Application::with(['program', 'benefit'])
            ->where('farmer_id', $farmer->id)
            ->where('status', 'approved')
            ->whereNotNull('program_benefit_id')
            ->latest('applied_at')
            ->get();

        return view('admin.distributions', [
            'farmer' => $farmer,
            'applications' => $applications,
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

    public function release(
        Request $request,
        AuditLogService $auditLogService
    ): RedirectResponse {
        $validated = $request->validate([
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'farmer_id' => ['nullable', 'integer', 'exists:farmers,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($validated['application_id']) &&
            (empty($validated['farmer_id']) || empty($validated['program_id']))) {
            return redirect()
                ->route('admin.distributions.index')
                ->with('error', 'An approved application is required.');
        }

        $result = DB::transaction(function () use ($validated, $request, $auditLogService) {
            $query = Application::query()
                ->where('status', 'approved')
                ->whereNotNull('program_benefit_id');

            if (! empty($validated['application_id'])) {
                $query->whereKey($validated['application_id']);
            } else {
                $query->where('farmer_id', $validated['farmer_id'])
                    ->where('program_id', $validated['program_id']);
            }

            $application = $query
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $application) {
                if (! empty($validated['application_id'])) {
                    $legacy = Application::find($validated['application_id']);

                    if ($legacy &&
                        $legacy->status === 'approved' &&
                        $legacy->program_benefit_id === null) {
                        return ['error' => 'legacy'];
                    }
                }

                return ['error' => 'not_found'];
            }

            $alreadyReleased = Distribution::query()
                ->where(function ($query) use ($application) {
                    $query->where('application_id', $application->id)
                        ->orWhere(function ($scope) use ($application) {
                            $scope->where('farmer_id', $application->farmer_id)
                                ->where('program_id', $application->program_id)
                                ->where('program_benefit_id', $application->program_benefit_id);
                        });
                })
                ->exists();

            if ($alreadyReleased) {
                return ['error' => 'already_released'];
            }

            $distribution = Distribution::create([
                'farmer_id' => $application->farmer_id,
                'program_id' => $application->program_id,
                'program_benefit_id' => $application->program_benefit_id,
                'application_id' => $application->id,
                'released_by' => $request->user()->id,
                'distributed_at' => now(),
                'notes' => $validated['notes'] ?? 'Released through admin distribution.',
            ]);

            $auditLogService->log(
                'BENEFIT_RELEASED',
                $distribution,
                "Application {$application->id} released through admin distribution."
            );

            return ['success' => true];
        });

        if (isset($result['error'])) {
            $message = match ($result['error']) {
                'legacy' => 'Legacy program-level applications cannot authorize benefit releases. The farmer must apply for a specific benefit.',
                'already_released' => 'This application or benefit has already been released.',
                default => 'Approved benefit application not found.',
            };

            return redirect()
                ->route('admin.distributions.index')
                ->with('error', $message);
        }

        return redirect()
            ->route('admin.distributions.index')
            ->with('success', 'Benefit released successfully.');
    }
}
