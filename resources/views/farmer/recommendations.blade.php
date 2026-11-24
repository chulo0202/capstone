@extends('layouts.farmer')

@section('page-title', 'Program Recommendations')

@section('main')

<style>
    .recommendations-table {
        min-width: 46rem;
        --bs-table-color: #172638;
        --bs-table-border-color: #e1ebef;
        --bs-table-hover-bg: #f2f8f5;
    }
    .recommendations-table > thead > tr > th {
        background: #edf3fa;
        color: #415974;
        font-size: .75rem;
        font-weight: 700;
        letter-spacing: .025em;
    }
    .recommendations-table > tbody > .recommendation-program > th {
        background: #e2f4eb;
        color: #164b35;
        padding-block: .55rem;
    }
    .recommendations-table .recommendation-row > td {
        padding-block: .8rem;
    }
    .recommendations-table .recommendation-row > td:first-child { width: 24%; }
    .recommendations-table .recommendation-row > td:nth-child(2) { width: 30%; }
    .recommendations-table .recommendation-row > td:nth-child(3) { width: 14%; }
    .recommendations-table .recommendation-row > td:nth-child(4) { width: 17%; }
    .recommendations-table .recommendation-row > td:last-child { width: 15%; }
    .recommendations-table .recommendation-row .btn {
        min-height: 2.45rem;
        border-radius: .6rem;
        font-weight: 600;
    }
    .recommendations-table .recommendation-row .btn-fams {
        background: #0d6033;
        color: #fff;
    }
    .recommendations-table .recommendation-row .btn-fams:hover {
        background: #084a27;
    }
    .recommendations-table [hidden] { display: none !important; }
</style>

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif


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
                                Eligible Benefits
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
                                Benefits evaluated
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

                    All Benefits

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

                        Recommended Benefits

                    </h6>

                    <small class="text-muted">
                        Benefits based on your current eligibility.
                    </small>

                </div>


                <span class="badge bg-light text-dark">

                    {{ $recommendations->count() }} benefit(s)

                </span>

            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive" tabindex="0" role="region" aria-label="Recommended programs and benefits">

                <table class="table table-hover align-middle mb-0 recommendations-table">

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

                        @forelse($recommendations->groupBy('program.id') as $programRecommendations)
                            <tr class="recommendation-program" data-program-id="{{ $programRecommendations->first()->program->id }}">
                                <th colspan="5" class="px-4"><i class="bi bi-leaf-fill me-2" aria-hidden="true"></i>{{ $programRecommendations->first()->program->name }}</th>
                            </tr>
                            @foreach($programRecommendations as $rec)

                            <tr
                                class="recommendation-row"
                                data-program-id="{{ $rec->program->id }}"
                                data-status="{{ $rec->eligibility_status }}"
                            >

                                {{-- PROGRAM --}}
                                <td class="px-4">

                                    <div class="fw-semibold text-dark">
                                        {{ $rec->benefit->name }}
                                    </div>
                                    <div class="small text-muted mt-1">
                                        <i class="bi bi-calendar3 me-1" aria-hidden="true"></i>
                                        @if ($rec->always_open)
                                            Open year-round
                                        @elseif ($rec->start_at && $rec->end_at)
                                            {{ $rec->start_at->format('M j, Y') }} – {{ $rec->end_at->format('M j, Y') }}
                                        @elseif ($rec->start_at)
                                            Opens {{ $rec->start_at->format('M j, Y') }}
                                        @elseif ($rec->end_at)
                                            Until {{ $rec->end_at->format('M j, Y') }}
                                        @else
                                            Schedule unavailable
                                        @endif
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

                                        <span class="badge rounded-pill text-bg-success px-3 py-2">

                                            <i class="bi bi-check-circle-fill me-1"></i>

                                            Eligible

                                        </span>

                                    @elseif($rec->eligibility_status === 'partially_eligible')

                                        <span
                                            class="badge rounded-pill text-bg-warning px-3 py-2"
                                        >

                                            <i class="bi bi-exclamation-circle-fill me-1"></i>

                                            Partially Eligible

                                        </span>

                                    @else
                                        <span class="badge rounded-pill text-bg-danger px-3 py-2">
                                            <i class="bi bi-x-circle-fill me-1" aria-hidden="true"></i> Not Eligible
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

                                    @elseif ($rec->eligibility_status !== 'eligible')

                                        <span class="small fw-semibold text-warning-emphasis">
                                            Complete missing requirements to apply.
                                        </span>

                                    @else

                                    @php
                                        $programName = $rec->program->name;
                                        $benefitName = $rec->benefit->name;

                                        $formType = match (true) {
                                            $programName === 'Agricultural Loans'
                                                => 'agricultural_loan',

                                            $programName === 'PCIC Crop Insurance'
                                                && $benefitName === 'Claim for Indemnity'
                                                => 'pcic_claim',

                                            $programName === 'PCIC Crop Insurance'
                                                => 'pcic_new_application',

                                            in_array($programName, [
                                                'Seed Subsidies',
                                                'Fertilizer Subsidies',
                                            ], true) => 'claim_intent',

                                            default => 'service_request',
                                        };

                                    @endphp

                                    <button
                                        type="button"
                                        class="btn btn-sm btn-fams px-3"
                                        data-bs-toggle="modal"
                                        data-bs-target="#programApplicationModal"
                                        data-application-url="{{ route(
                                            'farmer.applications.store',
                                            $rec->program
                                        ) }}"
                                        data-benefit-id="{{ $rec->benefit->id }}"
                                        data-benefit-name="{{ $rec->benefit->name }}"
                                        data-program-name="{{ $rec->program->name }}"
                                        data-form-type="{{ $formType }}"

                                    >
                                        <i class="bi bi-send me-1"></i>
                                        Apply Now
                                    </button>


                                    @endif


                                </td>

                            </tr>


                            @endforeach
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
                                        benefits open for applications.

                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>


        {{-- Program-specific application modal --}}
        @include('farmer.partials.application-modal')


        {{-- APPLICATION MODAL SCRIPT --}}
        <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('programApplicationModal');
            const form = document.getElementById('programApplicationForm');

            if (!modal || !form) {
                return;
            }

            const benefitId = document.getElementById('modalBenefitId');
            const modalTitle = document.getElementById('programApplicationModalLabel');
            const modalDescription = document.getElementById('modalBenefitDescription');
            const submitButton = document.getElementById('modalSubmitButton');

            modal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;

                if (!button || button.disabled) {
                    event.preventDefault();
                    return;
                }

                const formType = button.dataset.formType;
                const programName = button.dataset.programName;
                const benefitName = button.dataset.benefitName;

                // Clear previous application information.
                form.reset();
                form.removeAttribute('action');
                benefitId.value = '';

                // Hide and disable all program-specific fields.
                modal.querySelectorAll('.application-fields').forEach(function (section) {
                    section.classList.add('d-none');

                    section.querySelectorAll('input, select, textarea').forEach(function (field) {
                        field.disabled = true;
                    });
                });

                // Find the matching form section.
                const section = Array.from(
                    modal.querySelectorAll('.application-fields')
                ).find(function (element) {
                    return element.dataset.formType.split(/\s+/).includes(formType);
                });

                if (!section) {
                    event.preventDefault();
                    return;
                }

                section.classList.remove('d-none');

                section.querySelectorAll('input, select, textarea').forEach(function (field) {
                    field.disabled = false;
                });

                // Handle PCIC-specific fields.
                modal.querySelectorAll('.pcic-new-only').forEach(function (element) {
                    const visible = formType === 'pcic_new_application';

                    element.classList.toggle('d-none', !visible);

                    element.querySelectorAll('input, select, textarea').forEach(function (field) {
                        field.disabled = !visible;
                    });
                });

                modal.querySelectorAll('.pcic-claim-only').forEach(function (element) {
                    const visible = formType === 'pcic_claim';

                    element.classList.toggle('d-none', !visible);

                    element.querySelectorAll('input, select, textarea').forEach(function (field) {
                        field.disabled = !visible;
                    });
                });

                modalTitle.textContent = programName;
                modalDescription.textContent = benefitName;

                form.action = button.dataset.applicationUrl;
                benefitId.value = button.dataset.benefitId;

                submitButton.textContent = {
                    agricultural_loan: 'Submit Loan Application',
                    pcic_new_application: 'Submit Insurance Application',
                    pcic_claim: 'File Claim',
                    claim_intent: 'Confirm Intent to Claim',
                    service_request: 'Submit Service Request'
                }[formType] || 'Submit Application';

                submitButton.disabled = false;
            });
        });
        </script>



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


                    const visiblePrograms = new Set();
                    rows.forEach(function (row) {
                        const visible = filter === 'all' || row.dataset.status === filter;
                        row.hidden = !visible;

                        if (visible) {
                            visiblePrograms.add(row.dataset.programId);
                        }
                    });

                    document.querySelectorAll('.recommendation-program').forEach(function (heading) {
                        heading.hidden = !visiblePrograms.has(heading.dataset.programId);
                    });

                });

            });

        });

    </script>

@endif

@endsection