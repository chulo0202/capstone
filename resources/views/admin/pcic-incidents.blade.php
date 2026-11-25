
@extends('layouts.admin')

@section('page-title', 'PCIC Damage Incidents')
@section('page-subtitle', 'Review farmer-reported crop damage incidents')

@section('main')
<div class="fams-card">
    <div class="fams-card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h6 class="mb-1">
                <i class="bi bi-shield-check me-2 text-fams"></i>
                PCIC Damage Incident Review
            </h6>
            <small class="text-muted">
                Internal incident verification only — not official PCIC claim approval.
            </small>
        </div>

        <form method="GET" action="{{ route('admin.pcic-incidents.index') }}" class="d-flex gap-2">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                @foreach (['pending', 'verified', 'rejected'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>
                        {{ ucfirst($status) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-fams">Filter</button>
        </form>
    </div>

    <div class="p-3">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Incident Reference</th>
                        <th>Farmer ID</th>
                        <th>Policy</th>
                        <th>Date of Loss</th>
                        <th>Cause</th>
                        <th>Area (ha)</th>
                        <th>Status</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($incidents as $incident)
                        <tr>
                            <td>
                                <strong>{{ $incident->incident_reference }}</strong>
                                <div class="small text-muted">
                                    Application #{{ $incident->application_id }}
                                </div>
                            </td>
                            <td>{{ $incident->farmer_id }}</td>
                            <td>{{ $incident->insuranceCoverage?->policy_number ?? 'Unavailable' }}</td>
                            <td>{{ $incident->date_of_loss?->format('M d, Y') ?? '—' }}</td>
                            <td>{{ ucfirst($incident->cause_of_loss) }}</td>
                            <td>{{ $incident->area_damaged }}</td>
                            <td>
                                @php
                                    $badgeClass = match ($incident->status) {
                                        'verified' => 'bg-success',
                                        'rejected' => 'bg-danger',
                                        default => 'bg-warning text-dark',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">
                                    {{ ucfirst($incident->status) }}
                                </span>
                            </td>
                            <td>
                                <button
                                    type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#incident-review-{{ $incident->id }}"
                                    aria-expanded="false"
                                >
                                    Details
                                </button>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="8" class="p-0 border-0">
                                <div class="collapse" id="incident-review-{{ $incident->id }}">
                                    <div class="p-3 border rounded m-2">
                                        <h6>Incident Details</h6>

                                        <p class="mb-1">
                                            <strong>Crop:</strong> {{ $incident->crop_type }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>Insured area:</strong>
                                            {{ $incident->insuranceCoverage?->area_insured ?? 'Unavailable' }} ha
                                        </p>
                                        <p class="mb-1">
                                            <strong>Coverage period:</strong>
                                            {{ $incident->insuranceCoverage?->coverage_start_date?->format('M d, Y') ?? '—' }}
                                            to
                                            {{ $incident->insuranceCoverage?->coverage_end_date?->format('M d, Y') ?? '—' }}
                                        </p>
                                        <p class="mb-1">
                                            <strong>Damage percentage:</strong>
                                            {{ data_get($incident->application?->detail?->details, 'degree_of_damage', 'Not provided') }}
                                        </p>


                                        @if ($incident->damagePhotoPaths())
                                            @include('admin.applications.damage-photos')
                                        @else
                                            <p class="mb-3 text-muted small">
                                                No damage photo is available for this incident.
                                            </p>
                                        @endif

                                        @if ($incident->status === 'pending')
                                            <div class="row g-3">
                                                <div class="col-md-6">
                                                    <form method="POST" action="{{ route('admin.pcic-incidents.verify', $incident) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <label class="form-label fw-semibold">Verification Notes</label>
                                                        <textarea
                                                            name="review_notes"
                                                            class="form-control mb-2"
                                                            rows="3"
                                                            minlength="10"
                                                            maxlength="2000"
                                                            required
                                                        ></textarea>

                                                        <div class="form-check mb-2">
                                                            <input
                                                                type="checkbox"
                                                                name="verification_confirmed"
                                                                value="1"
                                                                class="form-check-input"
                                                                id="confirm-{{ $incident->id }}"
                                                                required
                                                            >
                                                            <label class="form-check-label" for="confirm-{{ $incident->id }}">
                                                                I reviewed the available incident and insurance evidence.
                                                            </label>
                                                        </div>

                                                        <button type="submit" class="btn btn-sm btn-success">
                                                            Verify Incident
                                                        </button>
                                                    </form>
                                                </div>

                                                <div class="col-md-6">
                                                    <form method="POST" action="{{ route('admin.pcic-incidents.reject', $incident) }}">
                                                        @csrf
                                                        @method('PATCH')

                                                        <label class="form-label fw-semibold">Rejection Reason</label>
                                                        <textarea
                                                            name="rejection_reason"
                                                            class="form-control mb-2"
                                                            rows="3"
                                                            minlength="10"
                                                            maxlength="2000"
                                                            required
                                                        ></textarea>

                                                        <button type="submit" class="btn btn-sm btn-danger">
                                                            Reject Incident
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @else
                                            <p class="mb-1">
                                                <strong>Review notes:</strong>
                                                {{ $incident->review_notes ?? 'None' }}
                                            </p>
                                            <p class="mb-0">
                                                <strong>Reviewed at:</strong>
                                                {{ $incident->reviewed_at?->format('M d, Y h:i A') ?? '—' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                No PCIC damage incidents found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            {{ $incidents->links() }}
        </div>
    </div>
</div>
@endsection
