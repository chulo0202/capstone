
@extends('layouts.admin')

@section('page-title', 'Application Details')

@section('main')

<div class="container-fluid py-4">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center gap-3 mb-4 flex-wrap">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <i class="bi bi-file-earmark-text text-success fs-3"></i>
                <h2 class="fw-bold mb-0">Application Details</h2>
            </div>
            <p class="text-muted mb-0">
                View the information submitted by the farmer.
            </p>
        </div>

        <a href="{{ route('admin.applications.index') }}"
           class="btn btn-fams-back">
            <i class="bi bi-arrow-left me-1"></i>
            Back to Applications
        </a>
    </div>

    {{-- FARMER INFORMATION --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-person me-2 text-success"></i>
                Farmer Information
            </h5>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small">Farmer Name</div>
                    <div class="fw-semibold">
                        {{ $application->farmer?->full_name ?? 'Not available' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">Application Date</div>
                    <div class="fw-semibold">
                        {{ $application->applied_at?->format('F d, Y h:i A') ?? 'Not available' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PROGRAM INFORMATION --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-journal-text me-2 text-success"></i>
                Program Information
            </h5>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small">Program</div>
                    <div class="fw-semibold">
                        {{ $application->program?->name ?? 'Not available' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">Selected Benefit</div>
                    <div class="fw-semibold">
                        {{ $application->benefit?->name ?? 'Not available' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">Application Type</div>
                    <div class="fw-semibold">
                        {{ $application->detail?->form_type
                            ? ucwords(str_replace('_', ' ', $application->detail->form_type))
                            : 'Not specified' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">Status</div>
                    @php
                        $status = strtolower($application->status ?? '');
                        $statusClass = match ($status) {
                            'approved' => 'bg-success',
                            'rejected' => 'bg-danger',
                            'pending' => 'bg-warning text-dark',
                            default => 'bg-secondary',
                        };
                    @endphp

                    <div>
                        <span class="badge {{ $statusClass }}">
                            {{ ucfirst($status ?: 'Unknown') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SUBMITTED FORM DETAILS --}}
    @php
        $details = $application->detail?->details ?? [];
        $isSimpleClaim = in_array(
            $application->program?->name,
            ['Seed Subsidies', 'Fertilizer Subsidies'],
            true
        );
    @endphp

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-ui-checks me-2 text-success"></i>
                Submitted Application Form
            </h5>
        </div>

        <div class="card-body">
            @if ($isSimpleClaim)
                <p class="mb-2">
                    <strong>Farmer:</strong>
                    {{ $application->farmer?->full_name ?? 'Not available' }}
                </p>
                <p class="mb-2">
                    <strong>Selected Benefit:</strong>
                    {{ $application->benefit?->name ?? 'Not available' }}
                </p>
                <p class="mb-0">
                    <strong>Request:</strong>
                    Intent to claim the selected benefit.
                </p>
            @elseif (is_array($details) && count($details))
                <div class="row g-3">
                    @foreach ($details as $key => $value)
                        @continue(in_array($key, ['damage_photo_path', 'damage_photo_paths', 'lots'], true))

                        <div class="col-md-6">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small mb-1">
                                    {{ ucwords(str_replace('_', ' ', $key)) }}
                                </div>

                                <div class="fw-semibold text-break">
                                    @if (is_array($value))
                                        {{ json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}
                                    @elseif (is_bool($value))
                                        {{ $value ? 'Yes' : 'No' }}
                                    @else
                                        {{ $value === null || $value === '' ? 'Not provided' : $value }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-muted mb-0">
                    No additional form details were recorded.
                </p>
            @endif

            @includeWhen(!empty($details['lots']), 'admin.applications.insured-lots', ['lots' => $details['lots'] ?? []])

            @if ($application->pcicDamageIncident)
                <hr>
                <p>
                    <strong>PCIC Incident Reference:</strong>
                    {{ $application->pcicDamageIncident->incident_reference }}
                </p>

                @includeWhen($application->pcicDamageIncident?->damagePhotoPaths(), 'admin.applications.damage-photos', ['incident' => $application->pcicDamageIncident])
            @endif
        </div>
    </div>

    {{-- REVIEW INFORMATION --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0">
                <i class="bi bi-clipboard-check me-2 text-success"></i>
                Review Information
            </h5>
        </div>

        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="text-muted small">Reviewed At</div>
                    <div class="fw-semibold">
                        {{ $application->reviewed_at?->format('F d, Y h:i A') ?? 'Not yet reviewed' }}
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="text-muted small">Remarks</div>
                    <div class="fw-semibold text-break">
                        {{ $application->remarks ?: 'No remarks' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
