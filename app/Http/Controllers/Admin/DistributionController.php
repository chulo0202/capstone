<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\Distribution;
use App\Models\Farmer;
use App\Models\Program;
use App\Services\DistributionReleaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $applications = Application::with(['program', 'benefit', 'activeDistributionAssignment.batch'])
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
        DistributionReleaseService $releases
    ): RedirectResponse {
        $validated = $request->validate([
            'application_id' => ['nullable', 'integer', 'exists:applications,id'],
            'farmer_id' => ['nullable', 'integer', 'exists:farmers,id'],
            'program_id' => ['nullable', 'integer', 'exists:programs,id'],
            'distribution_batch_id' => ['nullable', 'integer', 'exists:distribution_batches,id'],
            'confirm_release' => ['nullable', 'required_with:distribution_batch_id', 'in:1,yes,on,true'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        if (empty($validated['application_id']) &&
            (empty($validated['farmer_id']) || empty($validated['program_id']))) {
            return redirect()
                ->route('admin.distributions.index')
                ->with('error', 'An approved application is required.');
        }

        $result = $releases->release(
            admin: $request->user(),
            applicationId: $validated['application_id'] ?? null,
            notes: $validated['notes'] ?? 'Released through admin distribution.',
            batchId: $validated['distribution_batch_id'] ?? null,
            farmerId: $validated['farmer_id'] ?? null,
            programId: $validated['program_id'] ?? null,
        );

        if (isset($result['error'])) {
            $message = match ($result['error']) {
                'legacy_application_not_releasable' => 'Legacy program-level applications cannot authorize benefit releases. The farmer must apply for a specific benefit.',
                'already_released' => 'This application or benefit has already been released.',
                'wrong_batch' => 'This application is not assigned to the selected distribution batch.',
                'batch_not_releasable' => 'The assigned distribution must be published and within its scheduled release window.',
                'invalid_benefit' => 'This application does not reference a valid program benefit.',
                'pcic_approval_required' => 'Official PCIC approval is required before release.',
                'ineligible' => 'Benefit cannot be released because the farmer is not eligible.',
                default => 'Approved benefit application not found.',
            };

            return $request->filled('distribution_batch_id')
                ? to_route('admin.distribution-batches.show', $validated['distribution_batch_id'])->with('error', $message)
                : to_route('admin.distributions.index')->with('error', $message);
        }

        return $request->filled('distribution_batch_id')
            ? to_route('admin.distribution-batches.show', $validated['distribution_batch_id'])->with('success', 'Benefit released successfully.')
            : to_route('admin.distributions.index')->with('success', 'Benefit released successfully.');
    }
}
