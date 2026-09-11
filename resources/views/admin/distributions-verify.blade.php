@extends('layouts.admin')

@section('page-title', 'Verify & Release Benefit')

@section('main')

{{-- BACK TO SCANNER --}}
<div class="mb-3">
    <a href="{{ route('admin.distributions.scan') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>
        Back to Scanner
    </a>
</div>


{{-- FARMER VERIFIED --}}
<div class="fams-card mb-4">
    <div class="fams-card-header">
        <h6>
            <i class="bi bi-person-check me-2 text-fams"></i>
            Farmer Verified
        </h6>
    </div>

    <div class="fams-card-body">
        <div class="row g-4">

            <div class="col-md-6">
                <div class="verify-info">
                    <div class="verify-info-label">
                        <i class="bi bi-person me-1"></i>
                        Name
                    </div>

                    <div class="verify-info-value">
                        {{ $farmer->full_name }}
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="verify-info">
                    <div class="verify-info-label">
                        <i class="bi bi-geo-alt me-1"></i>
                        Barangay
                    </div>

                    <div class="verify-info-value">
                        {{ $farmer->barangay }}
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="verify-info">
                    <div class="verify-info-label">
                        <i class="bi bi-flower1 me-1"></i>
                        Crop
                    </div>

                    <div class="verify-info-value">
                        {{ $farmer->crop_type }}
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="verify-info">
                    <div class="verify-info-label">
                        <i class="bi bi-telephone me-1"></i>
                        Contact
                    </div>

                    <div class="verify-info-value">
                        {{ $farmer->contact_number }}
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>


{{-- PROGRAM ELIGIBILITY & RELEASE --}}
<div class="fams-card">

    <div class="fams-card-header verify-program-header">
        <h6>
            <i class="bi bi-box-seam me-2 text-fams"></i>
            Program Eligibility & Release
        </h6>

        <span class="program-count">
            <i class="bi bi-layers me-1"></i>
            {{ $programs->count() }} Programs
        </span>
    </div>


    <div class="fams-card-body p-0">

        <div class="table-responsive verify-table-wrapper">

            <table class="table fams-table verify-program-table mb-0">

                <thead>
                    <tr>
                        <th class="program-column">Program</th>
                        <th class="status-column">Eligibility</th>
                        <th class="application-column">Application</th>
                        <th class="requirements-column">Missing Requirements</th>
                        <th class="action-column">Action</th>
                    </tr>
                </thead>


                <tbody>

                    @forelse($programs as $item)

                        <tr>

                            {{-- PROGRAM --}}
                            <td class="program-cell">

                                <div class="program-name">
                                    {{ $item['program']->name }}
                                </div>

                                @if($item['program']->description)
                                    <div class="program-description">
                                        {{ $item['program']->description }}
                                    </div>
                                @endif

                            </td>


                            {{-- ELIGIBILITY --}}
                            <td class="status-cell">

                                @if($item['status'] === 'eligible')

                                    <span class="badge bg-success status-badge">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Eligible
                                    </span>

                                @elseif($item['status'] === 'partially_eligible')

                                    <span class="badge bg-warning text-dark status-badge">
                                        <i class="bi bi-exclamation-circle me-1"></i>
                                        Partially Eligible
                                    </span>

                                @else

                                    <span class="badge bg-danger status-badge">
                                        <i class="bi bi-x-circle me-1"></i>
                                        Not Eligible
                                    </span>

                                @endif

                            </td>


                            {{-- APPLICATION --}}
                            <td class="status-cell">

                                @if(!$item['application'])

                                    <span class="badge bg-secondary status-badge">
                                        <i class="bi bi-dash-circle me-1"></i>
                                        Not Applied
                                    </span>

                                @elseif($item['application_status'] === 'pending')

                                    <span class="badge bg-warning text-dark status-badge">
                                        <i class="bi bi-clock me-1"></i>
                                        Pending
                                    </span>

                                @elseif($item['application_status'] === 'approved')

                                    <span class="badge bg-success status-badge">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Approved
                                    </span>

                                @elseif($item['application_status'] === 'rejected')

                                    <span class="badge bg-danger status-badge">
                                        <i class="bi bi-x-circle me-1"></i>
                                        Rejected
                                    </span>

                                @else

                                    <span class="badge bg-secondary status-badge">
                                        <i class="bi bi-info-circle me-1"></i>
                                        {{ ucfirst($item['application_status']) }}
                                    </span>

                                @endif

                            </td>


                            {{-- MISSING REQUIREMENTS --}}
                            <td class="requirements-cell">

                                @if($item['missing'])

                                    <div class="requirement-missing">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        {{ implode('; ', $item['missing']) }}
                                    </div>

                                @else

                                    <div class="requirement-none">
                                        <i class="bi bi-check2-circle me-1"></i>
                                        None
                                    </div>

                                @endif

                            </td>


                            {{-- ACTION --}}
                            <td class="action-cell">

                                @if($item['already_released'])

                                    <span class="badge bg-secondary status-badge">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Released
                                    </span>


                                @elseif($item['status'] === 'eligible' && $item['application_status'] === 'approved')

                                    <form method="POST"
                                          action="{{ route('admin.distributions.release') }}"
                                          class="d-inline">

                                        @csrf

                                        <input type="hidden"
                                               name="farmer_id"
                                               value="{{ $farmer->id }}">

                                        <input type="hidden"
                                               name="program_id"
                                               value="{{ $item['program']->id }}">

                                        <button type="submit"
                                                class="btn btn-sm btn-fams release-btn"
                                                onclick="return confirm('Release this benefit to this farmer?')">

                                            <i class="bi bi-box-seam me-1"></i>
                                            Release Benefit

                                        </button>

                                    </form>


                                @elseif($item['status'] === 'eligible' && $item['application_status'] === 'pending')

                                    <span class="badge bg-warning text-dark status-badge">
                                        <i class="bi bi-clock me-1"></i>
                                        Waiting for Approval
                                    </span>


                                @elseif($item['status'] === 'eligible' && !$item['application'])

                                    <span class="action-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        Not Applied
                                    </span>


                                @elseif($item['application_status'] === 'rejected')

                                    <span class="action-danger">
                                        <i class="bi bi-x-circle me-1"></i>
                                        Application Rejected
                                    </span>


                                @else

                                    <span class="action-muted">
                                        <i class="bi bi-slash-circle me-1"></i>
                                        Not Eligible
                                    </span>

                                @endif

                            </td>

                        </tr>


                    @empty

                        <tr>
                            <td colspan="5" class="text-center py-5">

                                <div class="empty-programs">

                                    <i class="bi bi-box-seam"></i>

                                    <div class="fw-semibold">
                                        No Active Programs
                                    </div>

                                    <small>
                                        No active programs are currently available.
                                    </small>

                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection