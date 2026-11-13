@extends('layouts.farmer')

@section('page-title', 'Program Recommendations')

@section('main')

@if(!$farmer || !$farmer->profile_completed)

    <div class="alert alert-warning border-0 shadow-sm">

        <i class="bi bi-exclamation-circle me-2"></i>

        Complete your
        <a href="{{ route('farmer.profile') }}" class="fw-semibold">
            profile
        </a>
        to get program recommendations.

    </div>

@else

    @php
        $eligibleCount = $recommendations
            ->where('eligibility_status', 'eligible')
            ->count();

        $partialCount = $recommendations
            ->where('eligibility_status', 'partially_eligible')
            ->count();

        $notEligibleCount = $recommendations
            ->where('eligibility_status', 'not_eligible')
            ->count();
    @endphp


    {{-- =========================================================
        PAGE HEADER
    ========================================================== --}}
    <div class="d-flex flex-column flex-md-row
                justify-content-between align-items-md-center
                mb-4">

        <div>

            <h4 class="mb-1 fw-bold">
                Program Recommendations
            </h4>

            <p class="text-muted mb-0">
                Agricultural assistance programs matched to your farm profile.
            </p>

        </div>

    </div>


    {{-- =========================================================
        SUMMARY CARDS
    ========================================================== --}}
    <div class="row g-3 mb-4">

        {{-- ELIGIBLE --}}
        <div class="col-md-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <div class="text-muted small mb-1">
                                Eligible Programs
                            </div>

                            <h3 class="fw-bold text-success mb-0">
                                {{ $eligibleCount }}
                            </h3>

                            <small class="text-muted">
                                Ready to apply
                            </small>

                        </div>

                        <div
                            class="rounded-circle
                                   bg-success bg-opacity-10
                                   d-flex align-items-center
                                   justify-content-center"
                            style="width:50px;height:50px;"
                        >

                            <i class="bi bi-check-circle
                                      text-success fs-4"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- PARTIALLY ELIGIBLE --}}
        <div class="col-md-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <div class="text-muted small mb-1">
                                Partially Eligible
                            </div>

                            <h3 class="fw-bold text-warning mb-0">
                                {{ $partialCount }}
                            </h3>

                            <small class="text-muted">
                                Requirements missing
                            </small>

                        </div>

                        <div
                            class="rounded-circle
                                   bg-warning bg-opacity-10
                                   d-flex align-items-center
                                   justify-content-center"
                            style="width:50px;height:50px;"
                        >

                            <i class="bi bi-exclamation-circle
                                      text-warning fs-4"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- NOT ELIGIBLE --}}
        <div class="col-md-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <div class="text-muted small mb-1">
                                Not Eligible
                            </div>

                            <h3 class="fw-bold text-danger mb-0">
                                {{ $notEligibleCount }}
                            </h3>

                            <small class="text-muted">
                                Currently unavailable
                            </small>

                        </div>

                        <div
                            class="rounded-circle
                                   bg-danger bg-opacity-10
                                   d-flex align-items-center
                                   justify-content-center"
                            style="width:50px;height:50px;"
                        >

                            <i class="bi bi-x-circle
                                      text-danger fs-4"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ACTIVE RECOMMENDATIONS --}}
        <div class="col-md-6 col-xl-3">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="d-flex
                                justify-content-between
                                align-items-center">

                        <div>

                            <div class="text-muted small mb-1">
                                Recommendations
                            </div>

                            <h3 class="fw-bold text-primary mb-0">
                                {{ $recommendations->count() }}
                            </h3>

                            <small class="text-muted">
                                Available to apply
                            </small>

                        </div>

                        <div
                            class="rounded-circle
                                   bg-primary bg-opacity-10
                                   d-flex align-items-center
                                   justify-content-center"
                            style="width:50px;height:50px;"
                        >

                            <i class="bi bi-lightbulb
                                      text-primary fs-4"></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
        FILTERS
    ========================================================== --}}
    <div class="card border-0 shadow-sm mb-3">

        <div class="card-body py-3">

            <div class="d-flex flex-wrap gap-2">

                <button
                    type="button"
                    class="btn btn-sm btn-fams recommendation-filter active"
                    data-filter="all"
                >

                    <i class="bi bi-grid me-1"></i>

                    All Programs

                </button>


                <button
                    type="button"
                    class="btn btn-sm btn-outline-success recommendation-filter"
                    data-filter="eligible"
                >

                    <i class="bi bi-check-circle me-1"></i>

                    Eligible

                </button>


                <button
                    type="button"
                    class="btn btn-sm btn-outline-warning recommendation-filter"
                    data-filter="partially_eligible"
                >

                    <i class="bi bi-exclamation-circle me-1"></i>

                    Partially Eligible

                </button>


                <button
                    type="button"
                    class="btn btn-sm btn-outline-danger recommendation-filter"
                    data-filter="not_eligible"
                >

                    <i class="bi bi-x-circle me-1"></i>

                    Not Eligible

                </button>

            </div>

        </div>

    </div>


    {{-- =========================================================
        RECOMMENDATION TABLE
    ========================================================== --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white border-0 py-3">

            <div class="d-flex
                        justify-content-between
                        align-items-center">

                <div>

                    <h6 class="fw-bold mb-1">

                        <i class="bi bi-lightbulb me-2"></i>

                        Recommended Programs

                    </h6>

                    <small class="text-muted">
                        Programs based on your current eligibility.
                    </small>

                </div>


                <span class="badge bg-light text-dark">

                    {{ $recommendations->count() }} program(s)

                </span>

            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">

                        <tr>

                            <th class="px-4 py-3">
                                Program / Benefit
                            </th>

                            <th class="py-3">
                                Description
                            </th>

                            <th class="py-3 text-center">
                                Status
                            </th>

                            <th class="py-3">
                                Requirements
                            </th>

                            <th class="py-3 text-center">
                                Application
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($recommendations as $rec)

                            <tr
                                class="recommendation-row"
                                data-status="{{ $rec->eligibility_status }}"
                            >

                                {{-- PROGRAM --}}
                                <td class="px-4">

                                    <div class="fw-semibold text-dark">

                                        {{ $rec->program->name }}

                                    </div>
                                    <div class="small text-muted">
                                        {{ $rec->benefit->name }}
                                    </div>

                                </td>


                                {{-- DESCRIPTION --}}
                                <td>

                                    <div
                                        class="small text-muted"
                                        style="max-width: 320px;"
                                    >

                                        {{ Str::limit(
                                            $rec->program->description,
                                            100
                                        ) }}

                                    </div>

                                </td>


                                {{-- ELIGIBILITY STATUS --}}
                                <td class="text-center">

                                    @if($rec->eligibility_status === 'eligible')

                                        <span class="badge bg-success px-3 py-2">

                                            <i class="bi bi-check-circle-fill me-1"></i>

                                            Eligible

                                        </span>

                                    @elseif($rec->eligibility_status === 'partially_eligible')

                                        <span
                                            class="badge bg-warning text-dark px-3 py-2"
                                        >

                                            <i class="bi bi-exclamation-circle-fill me-1"></i>

                                            Partially Eligible

                                        </span>

                                    @endif

                                </td>


                                {{-- REQUIREMENTS --}}
                                <td>

                                    @if($rec->missing_requirements)

                                        <div class="small">

                                            <div class="text-danger fw-semibold mb-1">
                                                Missing:
                                            </div>

                                            <ul class="mb-0 ps-3">

                                                @foreach($rec->missing_requirements as $missing)

                                                    <li>
                                                        {{ $missing }}
                                                    </li>

                                                @endforeach

                                            </ul>

                                        </div>

                                    @else

                                        <span class="small text-success">

                                            <i class="bi bi-check-circle me-1"></i>

                                            All requirements met

                                        </span>

                                    @endif

                                </td>


                                {{-- APPLICATION --}}
                                <td class="text-center">

                                    @php
                                        $applicationStatus = $applicationStatuses["{$rec->program->id}:{$rec->benefit->id}"] ?? null;
                                    @endphp

                                    @if ($applicationStatus === 'rejected')

                                        <button type="button" class="btn btn-sm btn-outline-danger px-3" disabled>
                                            <i class="bi bi-x-circle me-1"></i>
                                            Rejected
                                        </button>

                                    @elseif ($applicationStatus !== null)

                                        <button type="button" class="btn btn-sm btn-outline-secondary px-3" disabled>
                                            <i class="bi bi-check-circle me-1"></i>
                                            Already applied
                                        </button>

                                    @else

                                        <form
                                            method="POST"
                                            action="{{ route(
                                                'farmer.applications.store',
                                                $rec->program
                                            ) }}"
                                        >
                                            @csrf
                                            <input
                                                type="hidden"
                                                name="program_benefit_id"
                                                value="{{ $rec->benefit->id }}"
                                            >

                                            <button
                                                type="submit"
                                                class="btn btn-sm
                                                       {{ $rec->eligibility_status === 'eligible'
                                                           ? 'btn-fams'
                                                           : 'btn-outline-warning'
                                                       }}
                                                       px-3"
                                            >
                                                <i class="bi bi-send me-1"></i>
                                                Apply Now
                                            </button>
                                        </form>

                                    @endif


                                    @if(
                                        $rec->eligibility_status ===
                                        'partially_eligible'
                                        &&
                                        $rec->missing_requirements
                                    )

                                        <div class="small text-muted mt-1">

                                            Some requirements are still missing.

                                        </div>

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
                                        class="bi bi-lightbulb
                                               text-muted fs-1 d-block mb-3"
                                    ></i>

                                    <h6 class="fw-semibold">
                                        No recommendations available
                                    </h6>

                                    <p class="text-muted small mb-0">

                                        There are currently no active
                                        programs matching your eligibility.

                                    </p>

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
    ========================================================== --}}
    <script>

        document.addEventListener('DOMContentLoaded', function () {

            const filterButtons =
                document.querySelectorAll('.recommendation-filter');

            const rows =
                document.querySelectorAll('.recommendation-row');


            filterButtons.forEach(function (button) {

                button.addEventListener('click', function () {

                    const filter = this.dataset.filter;


                    // Remove active state
                    filterButtons.forEach(function (btn) {

                        btn.classList.remove('active');

                    });


                    // Add active state
                    this.classList.add('active');


                    // Show / hide rows
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

@endif

@endsection