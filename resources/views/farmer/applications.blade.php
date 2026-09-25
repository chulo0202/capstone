@extends('layouts.farmer')

@section('page-title', 'My Applications')
@section('page-subtitle', 'Track your submitted assistance applications')

@section('main')

@php
use App\Models\Distribution;

$totalApplications = $applications->count();

$pendingApplications = $applications
    ->where('status', 'pending')
    ->count();

$approvedApplications = $applications
    ->where('status', 'approved')
    ->count();

$rejectedApplications = $applications
    ->where('status', 'rejected')
    ->count();

@endphp

{{-- =========================================================
SUMMARY CARDS
========================================================= --}}

<div class="row g-3 mb-4">

{{-- TOTAL --}}
<div class="col-md-6 col-xl-4">

    <div class="card border-0 shadow-sm h-100">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <div class="small text-muted mb-1">
                        Total Applications
                    </div>

                    <h3 class="fw-bold mb-0">
                        {{ $totalApplications }}
                    </h3>

                    <small class="text-muted">
                        Submitted applications
                    </small>

                </div>

                <div
                    class="rounded-circle bg-primary bg-opacity-10
                           d-flex align-items-center justify-content-center"
                    style="width:50px;height:50px;"
                >
                    <i class="bi bi-file-earmark-text text-primary fs-4"></i>
                </div>

            </div>

        </div>

    </div>

</div>


{{-- PENDING --}}
<div class="col-md-6 col-xl-4">

    <div class="card border-0 shadow-sm h-100">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <div class="small text-muted mb-1">
                        Pending
                    </div>

                    <h3 class="fw-bold text-warning mb-0">
                        {{ $pendingApplications }}
                    </h3>

                    <small class="text-muted">
                        Waiting for review
                    </small>

                </div>

                <div
                    class="rounded-circle bg-warning bg-opacity-10
                           d-flex align-items-center justify-content-center"
                    style="width:50px;height:50px;"
                >
                    <i class="bi bi-clock text-warning fs-4"></i>
                </div>

            </div>

        </div>

    </div>

</div>


{{-- APPROVED --}}
<div class="col-md-6 col-xl-4">

    <div class="card border-0 shadow-sm h-100">

        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <div class="small text-muted mb-1">
                        Approved
                    </div>

                    <h3 class="fw-bold text-success mb-0">
                        {{ $approvedApplications }}
                    </h3>

                    <small class="text-muted">
                        Approved applications
                    </small>

                </div>

                <div
                    class="rounded-circle bg-success bg-opacity-10
                           d-flex align-items-center justify-content-center"
                    style="width:50px;height:50px;"
                >
                    <i class="bi bi-check-circle text-success fs-4"></i>
                </div>

            </div>

        </div>

    </div>

</div>

</div>

{{-- =========================================================
FILTER BUTTONS
========================================================= --}}

<div class="card border-0 shadow-sm mb-3">

<div class="card-body py-3">

    <div class="d-flex flex-wrap gap-2">

        <button
            type="button"
            class="btn btn-sm btn-fams application-filter active"
            data-filter="all"
        >
            <i class="bi bi-grid me-1"></i>
            All
        </button>


        <button
            type="button"
            class="btn btn-sm btn-outline-warning application-filter"
            data-filter="pending"
        >
            <i class="bi bi-clock me-1"></i>
            Pending
        </button>


        <button
            type="button"
            class="btn btn-sm btn-outline-success application-filter"
            data-filter="approved"
        >
            <i class="bi bi-check-circle me-1"></i>
            Approved
        </button>


        <button
            type="button"
            class="btn btn-sm btn-outline-danger application-filter"
            data-filter="rejected"
        >
            <i class="bi bi-x-circle me-1"></i>
            Rejected
        </button>

    </div>

</div>

</div>

{{-- =========================================================
APPLICATION HISTORY
========================================================= --}}

<div class="fams-card shadow-sm border-0">

<div class="fams-card-header">

    <div class="d-flex justify-content-between align-items-center">

        <div>

            <h6 class="mb-1">
                <i class="bi bi-file-earmark-check me-2 text-fams"></i>
                Application History
            </h6>

            <small class="text-muted">
                View the status of your submitted applications.
            </small>

        </div>

        <span class="badge bg-light text-dark">
            {{ $totalApplications }} application(s)
        </span>

    </div>

</div>


<div class="fams-card-body p-0">

    <div class="table-responsive">

        <table class="table fams-table table-hover align-middle mb-0">

            <thead>

                <tr>

                    <th class="px-4">
                        Program
                    </th>

                    <th>
                        Date Applied
                    </th>

                    <th class="text-center">
                        Status
                    </th>

                    <th>
                        Remarks
                    </th>

                    <th class="text-center">
                        Next Step
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($applications as $application)

                    @php
                        $alreadyReleased = false;

                        if ($application->status === 'approved') {
                            $alreadyReleased = Distribution::where('farmer_id', $application->farmer_id)
                                ->where('program_id', $application->program_id)
                                ->exists();
                        }
                    @endphp

                    <tr
                        class="application-row"
                        data-status="{{ $application->status }}"
                    >

                        {{-- PROGRAM --}}
                        <td class="px-4">

                            <div class="fw-semibold">

                                {{ $application->program->name }}

                            </div>

                        </td>


                        {{-- DATE --}}
                        <td>

                            <span class="small">

                                {{ $application->applied_at?->format('M d, Y') ?? '-' }}

                            </span>

                            @if($application->applied_at)

                                <div class="small text-muted">

                                    {{ $application->applied_at->format('h:i A') }}

                                </div>

                            @endif

                        </td>


                        {{-- STATUS --}}
                        <td class="text-center">

                            @if($application->status === 'approved')

                                <span class="badge bg-success px-3 py-2">

                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    Approved

                                </span>

                            @elseif($application->status === 'rejected')

                                <span class="badge bg-danger px-3 py-2">

                                    <i class="bi bi-x-circle-fill me-1"></i>
                                    Rejected

                                </span>

                            @elseif($application->status === 'pending')

                                <span class="badge bg-warning text-dark px-3 py-2">

                                    <i class="bi bi-clock-fill me-1"></i>
                                    Pending

                                </span>

                            @else

                                <span class="badge bg-secondary px-3 py-2">

                                    {{ ucfirst($application->status) }}

                                </span>

                            @endif

                        </td>


                        {{-- REMARKS --}}
                        <td>

                            <div
                                class="small text-muted"
                                style="max-width: 300px;"
                            >

                                {{ $application->remarks ?? 'No remarks provided.' }}

                            </div>

                        </td>


                        {{-- NEXT STEP --}}
                        <td class="text-center">

                            @if($application->status === 'pending')

                                <span class="small text-warning fw-semibold">

                                    <i class="bi bi-hourglass-split me-1"></i>
                                    Waiting for approval

                                </span>

                            @elseif($application->status === 'approved')

                                @if($alreadyReleased)

                                    <span class="small text-secondary fw-semibold">

                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Benefit already released

                                    </span>

                                @else

                                    <span class="small text-success fw-semibold">

                                        <i class="bi bi-box-seam me-1"></i>
                                        Ready for distribution

                                    </span>

                                @endif

                            @elseif($application->status === 'rejected')

                                <span class="small text-danger fw-semibold">

                                    <i class="bi bi-info-circle me-1"></i>
                                    Review remarks

                                </span>

                            @else

                                <span class="small text-muted">

                                    No action required

                                </span>

                            @endif

                        </td>

                    </tr>


                @empty

                    <tr>

                        <td
                            colspan="5"
                            class="text-center py-5"
                        >

                            <i
                                class="bi bi-file-earmark-text
                                       text-muted fs-1 d-block mb-3"
                            ></i>

                            <h6 class="fw-semibold">
                                No applications yet
                            </h6>

                            <p class="text-muted small mb-3">
                                You have not submitted any assistance applications.
                            </p>

                            <a
                                href="{{ route('farmer.recommendations') }}"
                                class="btn btn-sm btn-fams"
                            >
                                <i class="bi bi-lightbulb me-1"></i>
                                View Recommendations
                            </a>

                        </td>

                    </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

</div>

{{-- =========================================================
FILTER SCRIPT
========================================================= --}}

<script>

document.addEventListener('DOMContentLoaded', function () {

    const filterButtons =
        document.querySelectorAll('.application-filter');

    const rows =
        document.querySelectorAll('.application-row');


    filterButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const filter = this.dataset.filter;


            // Remove active state
            filterButtons.forEach(function (btn) {

                btn.classList.remove('active');

            });


            // Add active state
            this.classList.add('active');


            // Filter table rows
            rows.forEach(function (row) {

                const status =
                    row.dataset.status;


                if (
                    filter === 'all' ||
                    status === filter
                ) {

                    row.style.display = '';

                } else {

                    row.style.display = 'none';

                }

            });

        });

    });

});

</script>

@endsection
