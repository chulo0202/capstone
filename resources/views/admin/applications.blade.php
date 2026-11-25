@extends('layouts.admin')

@section('page-title', 'Farmer Applications')
@section('page-subtitle', 'Review and manage farmer program applications')

@section('main')

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif


<div class="fams-card">

    <div class="fams-card-header d-flex flex-wrap justify-content-between align-items-center gap-3">

        <div>
            <h6 class="mb-1">
                <i class="bi bi-file-earmark-text-fill me-2 text-fams"></i>
                Farmer Applications
            </h6>
            <small class="text-muted">
                Review applications submitted by farmers
            </small>
        </div>

        <form method="GET"
            action="{{ url()->current() }}"
            class="d-flex flex-wrap align-items-center justify-content-end gap-2 ms-auto">

            <input
                type="search"
                name="search"
                class="form-control form-control-sm"
                placeholder="Search farmer or program..."
                value="{{ request('search') }}"
                aria-label="Search applications"
                style="width: 230px; max-width: 100%;"
            >

            <select
                name="status"
                class="form-select form-select-sm"
                aria-label="Filter applications by status"
                style="width: 145px;"
            >
                <option value="">All Statuses</option>
                <option value="pending" @selected(request('status') === 'pending')>Pending</option>
                <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Rejected</option>
            </select>

            <button
                type="submit"
                class="btn btn-sm btn-fams px-3"
                title="Search applications"
                aria-label="Search applications"
            >
                <i class="bi bi-search"></i>
            </button>

        </form>

    </div>

    <div class="fams-card-body p-0">

        <div class="table-responsive">

            <table class="table fams-table mb-0">

                <thead>
                    <tr>
                        <th>Farmer</th>
                        <th>Program</th>
                        <th>Benefit</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                        <th>Reviewed</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($applications as $application)

                        <tr>

                            {{-- Farmer --}}
                            <td>
                                <strong>
                                    {{ $application->farmer?->full_name ?? 'Unknown Farmer' }}
                                </strong>

                                @if($application->farmer?->user)
                                    <div class="small text-muted">
                                        {{ $application->farmer->user->email }}
                                    </div>
                                @endif
                            </td>

                            {{-- Program --}}
                            <td>
                                {{ $application->program?->name ?? 'Unknown Program' }}
                            </td>

                            <td>
                                {{ $application->benefit?->name ?? 'Legacy application' }}
                            </td>

                            {{-- Applied Date --}}
                            <td>
                                {{ $application->applied_at?->format('M d, Y h:i A') ?? '—' }}
                            </td>

                            {{-- Status --}}
                            <td>

                                @if($application->status === 'approved')

                                    <span class="badge bg-success">
                                        Approved
                                    </span>

                                @elseif($application->status === 'rejected')

                                    <span class="badge bg-danger">
                                        Rejected
                                    </span>

                                @else

                                    <span class="badge bg-warning text-dark">
                                        Pending
                                    </span>

                                @endif

                            </td>

                            {{-- Reviewed Date --}}
                            <td>
                                {{ $application->reviewed_at?->format('M d, Y h:i A') ?? 'Not reviewed' }}
                            </td>

                            {{-- Actions --}}
                            <td class="text-center align-middle">
                                <a href="{{ route('admin.applications.show', $application) }}"
                                   class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center p-2"
                                   title="View details" aria-label="View application details">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24"
                                         fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"
                                         stroke-linejoin="round" aria-hidden="true" focusable="false">
                                        <path d="M11 21H4V3h9l5 5v3M13 3v5h5"/>
                                        <circle cx="16.5" cy="16.5" r="3.5"/>
                                        <path d="m19 19 3 3"/>
                                    </svg>
                                </a>

                                @if($application->status === 'pending')

                                    <div class="d-flex justify-content-center gap-1 mt-2">

                                        {{-- Approve --}}
                                        <form
                                            method="POST"
                                            action="{{ route('admin.applications.approve', $application) }}"
                                        >
                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                class="btn btn-sm btn-success"
                                                title="Approve" aria-label="Approve application"
                                            >
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-sm btn-danger" title="Reject" aria-label="Reject application"
                                            data-bs-toggle="modal" data-bs-target="#rejectApplicationModal"
                                            data-reject-url="{{ route('admin.applications.reject', $application) }}"
                                            data-application-id="{{ $application->id }}"
                                            data-farmer-name="{{ $application->farmer?->full_name }}">
                                            <i class="bi bi-x-lg" aria-hidden="true"></i>
                                        </button>

                                    </div>

                                @endif

                            </td>

                        </tr>

                        <tr>
                            <td colspan="7" class="p-0 border-0">
                                <div
                                    class="d-none"
                                    id="application-details-{{ $application->id }}"
                                >
                                    <div class="p-3 border rounded m-2">
                                        <h6 class="mb-3">Application Details</h6>
                                        @if ($application->program?->isPcicCropInsurance())
                                            <form method="POST" action="{{ route('admin.applications.pcic-tracking', $application) }}" class="border rounded p-3 mb-3">
                                                @csrf
                                                <h6>Post PCIC tracking update</h6>
                                                <div class="row g-2">
                                                    <label class="col-md-6">Status
                                                        <select class="form-select" name="status" required>
                                                            @foreach (\App\Models\PcicTrackingUpdate::ADMIN_STATUSES as $status)
                                                                <option value="{{ $status }}">{{ \App\Models\PcicTrackingUpdate::STATUSES[$status] }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label class="col-md-6">Expected release date
                                                        <input class="form-control" type="date" name="expected_release_date">
                                                    </label>
                                                    <label class="col-12">Official PCIC reference (required for PCIC decisions and release updates)
                                                        <input class="form-control" name="pcic_reference" maxlength="255">
                                                    </label>
                                                    <label class="col-12">Remarks visible to the farmer
                                                        <textarea class="form-control" name="remarks" maxlength="2000" rows="2"></textarea>
                                                    </label>
                                                </div>
                                                <button class="btn btn-fams mt-2">Post update</button>
                                            </form>
                                        @endif

                                        @php
                                            $formType = $application->detail?->form_type;
                                            $details = $application->detail?->details ?? [];

                                            $isSimpleClaim = in_array(
                                                $application->program?->name,
                                                ['Seed Subsidies', 'Fertilizer Subsidies'],
                                                true
                                            );
                                        @endphp

                                        @if ($formType)
                                            <p class="mb-2">
                                                <strong>Form Type:</strong>
                                                {{ ucwords(str_replace('_', ' ', $formType)) }}
                                            </p>
                                        @endif

                                        @if ($application->pcicDamageIncident)
                                            <p class="mb-2">
                                                <strong>PCIC Incident Reference:</strong>
                                                {{ $application->pcicDamageIncident->incident_reference }}
                                            </p>
                                        @endif

                                        @if ($isSimpleClaim)
                                            <p class="mb-2">
                                                <strong>Farmer:</strong>
                                                {{ $application->farmer?->full_name ?? 'Unknown' }}
                                            </p>
                                            <p class="mb-2">
                                                <strong>Selected Benefit:</strong>
                                                {{ $application->benefit?->name ?? 'Not specified' }}
                                            </p>
                                            <p class="mb-2">
                                                <strong>Intent to Claim:</strong>
                                                {{ !empty($details['confirm_intent']) ? 'Confirmed' : 'Not confirmed' }}
                                            </p>
                                        @elseif (!empty($details))
                                            @foreach ($details as $key => $value)
                                                @continue(in_array($key, ['damage_photo_path', 'damage_photo_paths', 'lots'], true))

                                                <p class="mb-2">
                                                    <strong>
                                                        {{ ucwords(str_replace('_', ' ', $key)) }}:
                                                    </strong>

                                                    @if (is_array($value))
                                                        {{ implode(', ', array_map(
                                                            fn ($item) => is_scalar($item) ? (string) $item : '[complex value]',
                                                            $value
                                                        )) }}
                                                    @elseif (is_bool($value))
                                                        {{ $value ? 'Yes' : 'No' }}
                                                    @else
                                                        {{ $value === null || $value === '' ? 'Not provided' : $value }}
                                                    @endif
                                                </p>
                                            @endforeach

                                            @includeWhen(!empty($details['lots']), 'admin.applications.insured-lots', ['lots' => $details['lots'] ?? []])

                                            @includeWhen($application->pcicDamageIncident?->damagePhotoPaths(), 'admin.applications.damage-photos', ['incident' => $application->pcicDamageIncident])
                                        @else
                                            <p class="text-muted mb-0">
                                                No additional application details available.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>


                    @empty

                        <tr>

                            <td colspan="7">

                                <div class="fams-empty">

                                    <i class="bi bi-file-earmark-text"></i>

                                    <div class="mt-2">
                                        No applications found.
                                    </div>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    @if($applications->hasPages())

        <div class="fams-card-body border-top py-3">

            {{ $applications->links() }}

        </div>

    @endif

</div>

<div class="modal fade" id="rejectApplicationModal" tabindex="-1" aria-labelledby="rejectApplicationTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="rejectApplicationForm" method="POST">
            @csrf
            @method('PATCH')
            <input type="hidden" name="rejection_application" value="">
            <div class="modal-header">
                <h2 class="modal-title h5" id="rejectApplicationTitle">Reject application</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted" id="rejectApplicationFarmer"></p>
                <label class="form-label" for="applicationRejectionReason">Reason for rejection <span class="text-danger">*</span></label>
                <textarea class="form-control" name="remarks" id="applicationRejectionReason"
                    rows="4" maxlength="2000" required></textarea>
                @error('remarks', 'applicationRejection')
                    <p class="small text-danger mt-2 mb-0" id="applicationRejectionError" hidden>{{ $message }}</p>
                @enderror
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject Application</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.getElementById('rejectApplicationModal');
    const form = document.getElementById('rejectApplicationForm');
    const reason = form.elements.remarks;
    const error = document.getElementById('applicationRejectionError');
    const oldId = {{ Js::from((string) old('rejection_application', '')) }};
    const oldReason = {{ Js::from((string) old('remarks', '')) }};

    modal.addEventListener('show.bs.modal', event => {
        const button = event.relatedTarget;
        if (!button?.dataset.rejectUrl) {
            event.preventDefault();
            return;
        }

        const failed = Boolean(error && button.dataset.applicationId === oldId);
        form.action = button.dataset.rejectUrl;
        form.elements.rejection_application.value = button.dataset.applicationId;
        document.getElementById('rejectApplicationFarmer').textContent = button.dataset.farmerName;
        reason.value = failed ? oldReason : '';
        reason.setCustomValidity('');
        reason.classList.toggle('is-invalid', failed);
        if (error) error.hidden = !failed;
    });

    modal.addEventListener('shown.bs.modal', () => reason.focus());
    reason.addEventListener('input', () => {
        reason.setCustomValidity(reason.value && !reason.value.trim() ? 'Enter a reason for rejection.' : '');
        reason.classList.remove('is-invalid');
        if (error) error.hidden = true;
    });

    if (error && oldId) {
        const trigger = [...document.querySelectorAll('[data-reject-url]')]
            .find(button => button.dataset.applicationId === oldId);
        if (trigger) bootstrap.Modal.getOrCreateInstance(modal).show(trigger);
    }
});
</script>

@endsection