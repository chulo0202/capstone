<?php

namespace App\Http\Controllers\Admin;

use Illuminate\View\View;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PcicDamageIncident;
use App\Models\PcicInsuranceCoverage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PcicDamageIncidentController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'status' => ['nullable', 'in:pending,verified,rejected'],
        ]);

        $incidents = PcicDamageIncident::query()
            ->with([
                'farmer',
                'insuranceCoverage',
                'application.detail',
                'reviewer',
            ])
            ->when(
                ! empty($validated['status']),
                fn ($query) => $query->where(
                    'status',
                    $validated['status']
                )
            )
            ->latest('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.pcic-incidents', compact('incidents'));
    }
    public function verify(
        Request $request,
        PcicDamageIncident $incident
    ): RedirectResponse {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'verification_confirmed' => [
                'required',
                'accepted',
            ],
            'review_notes' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
        ]);

        DB::transaction(function () use ($request, $incident, $validated) {
            $lockedIncident = PcicDamageIncident::query()
                ->whereKey($incident->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedIncident->status !== 'pending') {
                abort(409, 'Only pending incidents can be verified.');
            }

            $coverage = $lockedIncident->insuranceCoverage;

            if ($lockedIncident->pcic_insurance_coverage_id === null) {
                $matching = PcicInsuranceCoverage::query()
                    ->where('farmer_id', $lockedIncident->farmer_id)
                    ->where('verification_status', 'verified')
                    ->whereNotNull('verified_by')
                    ->whereNotNull('verified_at')
                    ->whereDate('coverage_start_date', '<=', $lockedIncident->date_of_loss)
                    ->whereDate('coverage_end_date', '>=', $lockedIncident->date_of_loss)
                    ->where('area_insured', '>=', $lockedIncident->area_damaged)
                    ->lockForUpdate()
                    ->get()
                    ->filter(fn ($policy) => strcasecmp(trim($policy->crop_type), trim($lockedIncident->crop_type)) === 0);

                abort_if($matching->count() > 1, 422, 'More than one verified insurance policy matches this incident.');
                $coverage = $matching->first();
            }

            if (! $coverage || ! $coverage->isVerified()) {
                abort(422, 'The insurance policy must be verified.');
            }

            if (
                $lockedIncident->date_of_loss->lt($coverage->coverage_start_date)
                || $lockedIncident->date_of_loss->gt($coverage->coverage_end_date)
                || (float) $lockedIncident->area_damaged > (float) $coverage->area_insured
                || (float) $lockedIncident->area_damaged <= 0
            ) {
                abort(422, 'Incident details do not match the insurance coverage.');
            }

            if (
                strcasecmp(
                    trim((string) $lockedIncident->crop_type),
                    trim((string) $coverage->crop_type)
                ) !== 0
            ) {
                abort(422, 'Incident crop does not match the insured crop.');
            }

            $lockedIncident->update([
                'pcic_insurance_coverage_id' => $coverage->id,
                'status' => 'verified',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_notes' => $validated['review_notes'],
            ]);

            $lockedIncident->application?->pcicTrackingUpdates()->create([
                'user_id' => $request->user()->id,
                'status' => 'incident_verified',
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'PCIC_INCIDENT_VERIFIED',
                'model' => 'PcicDamageIncident',
                'model_id' => $lockedIncident->id,
                'details' => [
                    'incident_reference' => $lockedIncident->incident_reference,
                    'application_id' => $lockedIncident->application_id,
                    'review_notes' => $validated['review_notes'],
                ],
            ]);
        });

        return back()->with(
            'success',
            'PCIC damage incident internally verified.'
        );
    }

    public function reject(
        Request $request,
        PcicDamageIncident $incident
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

        DB::transaction(function () use ($request, $incident, $validated) {
            $lockedIncident = PcicDamageIncident::query()
                ->whereKey($incident->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedIncident->status !== 'pending') {
                abort(409, 'Only pending incidents can be rejected.');
            }

            $lockedIncident->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'review_notes' => $validated['rejection_reason'],
            ]);

            $lockedIncident->application?->pcicTrackingUpdates()->create([
                'user_id' => $request->user()->id,
                'status' => 'incident_rejected',
            ]);

            AuditLog::create([
                'user_id' => $request->user()->id,
                'action' => 'PCIC_INCIDENT_REJECTED',
                'model' => 'PcicDamageIncident',
                'model_id' => $lockedIncident->id,
                'details' => [
                    'incident_reference' => $lockedIncident->incident_reference,
                    'application_id' => $lockedIncident->application_id,
                    'rejection_reason' => $validated['rejection_reason'],
                ],
            ]);
        });

        return back()->with(
            'success',
            'PCIC damage incident rejected.'
        );
    }

    public function damagePhoto(
        Request $request,
        PcicDamageIncident $incident,
        int $photo = 1
    ): StreamedResponse {
        abort_unless($request->user()?->isAdmin(), 403);

        $application = $incident->application;

        abort_unless(
            $application
                && (int) $application->farmer_id === (int) $incident->farmer_id,
            404
        );

        $path = $incident->damagePhotoPaths()[$photo - 1] ?? null;

        abort_unless(is_string($path) && $path !== '', 404);

        // Only permit files inside this farmer's private claim folder.
        $prefix = 'pcic-claims/' . $incident->farmer_id . '/';

        abort_unless(
            str_starts_with($path, $prefix)
                && ! str_contains($path, '..')
                && ! str_contains($path, '\\'),
            404
        );

        $disk = Storage::disk('private');

        abort_unless($disk->exists($path), 404);

        $mime = $disk->mimeType($path);

        abort_unless(
            in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true),
            415
        );

        $stream = $disk->readStream($path);

        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream) {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="pcic-damage-photo"',
            'Cache-Control' => 'private, no-store, max-age=0',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
