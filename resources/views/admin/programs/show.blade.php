@extends('layouts.admin')

@section('page-title', 'Program View')

@section('main')

<div class="container-fluid py-4">

    {{-- ============================================================
         HEADER
         ============================================================ --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <div class="d-flex align-items-center gap-2 mb-1">

                <i class="bi bi-journal-text text-success fs-3"></i>

                <h2 class="fw-bold mb-0">
                    Program Details
                </h2>

            </div>

            <p class="text-muted mb-0">
                View program information, benefits, schedule, and eligibility rules.
            </p>

        </div>


        <a
            href="{{ route('admin.programs.index') }}"
            class="btn btn-outline-secondary"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Back to Program List
        </a>

    </div>


    {{-- ============================================================
         AUTOMATIC STATUS
         ============================================================ --}}
    @php

        $now = now();

        if (
            $program->start_at &&
            $now->lt($program->start_at)
        ) {

            $statusLabel = 'Upcoming';
            $statusClass = 'status-upcoming';

        } elseif (
            $program->start_at &&
            $program->end_at &&
            $now->between(
                $program->start_at,
                $program->end_at
            )
        ) {

            $statusLabel = 'Active';
            $statusClass = 'status-active';

        } else {

            $statusLabel = 'Inactive';
            $statusClass = 'status-inactive';

        }

    @endphp


    {{-- ============================================================
         PROGRAM INFORMATION
         ============================================================ --}}
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-header bg-white py-3">

            <div class="d-flex align-items-center gap-3">

                <div class="program-view-icon">

                    <i class="bi bi-journal-text"></i>

                </div>


                <div>

                    <h4 class="fw-bold mb-1">
                        {{ $program->name }}
                    </h4>

                    <span class="program-status {{ $statusClass }}">

                        <span class="status-dot"></span>

                        {{ $statusLabel }}

                    </span>

                </div>

            </div>

        </div>


        <div class="card-body">

            {{-- Description --}}
            <div class="mb-4">

                <h6 class="fw-bold mb-2">
                    Description
                </h6>

                <p class="text-muted mb-0">

                    {{ $program->description ?: 'No description available.' }}

                </p>

            </div>


            {{-- Schedule --}}
            <div class="row">

                <div class="col-md-6 mb-3">

                    <div class="schedule-box">

                        <div class="schedule-icon">

                            <i class="bi bi-calendar-event"></i>

                        </div>

                        <div>

                            <small class="text-muted d-block">
                                Start Date & Time
                            </small>

                            <strong>

                                @if($program->start_at)

                                    {{ $program->start_at->format('F d, Y') }}

                                    <span class="text-muted fw-normal">
                                        at
                                    </span>

                                    {{ $program->start_at->format('h:i A') }}

                                @else

                                    —

                                @endif

                            </strong>

                        </div>

                    </div>

                </div>


                <div class="col-md-6 mb-3">

                    <div class="schedule-box">

                        <div class="schedule-icon">

                            <i class="bi bi-calendar-check"></i>

                        </div>

                        <div>

                            <small class="text-muted d-block">
                                End Date & Time
                            </small>

                            <strong>

                                @if($program->end_at)

                                    {{ $program->end_at->format('F d, Y') }}

                                    <span class="text-muted fw-normal">
                                        at
                                    </span>

                                    {{ $program->end_at->format('h:i A') }}

                                @else

                                    —

                                @endif

                            </strong>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ============================================================
         BENEFITS
         ============================================================ --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="fw-bold mb-1">
                        Program Benefits
                    </h5>

                    <small class="text-muted">
                        Benefits and their eligibility requirements.
                    </small>

                </div>


                <span class="benefit-total">

                    {{ $program->benefits->count() }}

                    {{ $program->benefits->count() === 1 ? 'Benefit' : 'Benefits' }}

                </span>

            </div>

        </div>


        <div class="card-body">

            @forelse($program->benefits as $benefit)

                {{-- ====================================================
                     BENEFIT CARD
                     ==================================================== --}}
                <div class="benefit-card mb-4">

                    <div class="d-flex align-items-start gap-3">

                        <div class="benefit-icon">

                            <i class="bi bi-gift"></i>

                        </div>


                        <div class="flex-grow-1">

                            <h6 class="fw-bold mb-1">

                                {{ $benefit->name }}

                            </h6>


                            <p class="text-muted mb-3">

                                {{ $benefit->description ?: 'No benefit description available.' }}

                            </p>


                            {{-- =================================================
                                 ELIGIBILITY RULE
                                 ================================================= --}}
                            @php

                                $eligibilityRule =
                                    $benefit->eligibilityRules->first();

                            @endphp


                            <div class="eligibility-box">

                                <div class="d-flex align-items-center gap-2 mb-3">

                                    <i class="bi bi-shield-check text-success"></i>

                                    <strong>
                                        Eligibility Requirements
                                    </strong>

                                </div>


                                @if($eligibilityRule)

                                    <div class="row">

                                        {{-- Crop Types --}}
                                        <div class="col-md-6 mb-3">

                                            <small class="text-muted d-block mb-1">
                                                Allowed Crop Types
                                            </small>

                                            @if(
                                                is_array($eligibilityRule->crop_types)
                                                && count($eligibilityRule->crop_types)
                                            )

                                                @foreach(
                                                    $eligibilityRule->crop_types
                                                    as $crop
                                                )

                                                    <span class="requirement-badge">
                                                        {{ $crop }}
                                                    </span>

                                                @endforeach

                                            @else

                                                <span class="text-muted">
                                                    No crop restriction
                                                </span>

                                            @endif

                                        </div>


                                        {{-- Land Size --}}
                                        <div class="col-md-6 mb-3">

                                            <small class="text-muted d-block mb-1">
                                                Land Size
                                            </small>

                                            @if(
                                                $eligibilityRule->min_land_size !== null
                                                &&
                                                $eligibilityRule->max_land_size !== null
                                            )

                                                {{ $eligibilityRule->min_land_size }}
                                                –
                                                {{ $eligibilityRule->max_land_size }}
                                                hectares

                                            @elseif(
                                                $eligibilityRule->min_land_size !== null
                                            )

                                                Minimum
                                                {{ $eligibilityRule->min_land_size }}
                                                hectares

                                            @elseif(
                                                $eligibilityRule->max_land_size !== null
                                            )

                                                Maximum
                                                {{ $eligibilityRule->max_land_size }}
                                                hectares

                                            @else

                                                <span class="text-muted">
                                                    No land size restriction
                                                </span>

                                            @endif

                                        </div>


                                        {{-- RSBSA --}}
                                        <div class="col-md-4 mb-2">

                                            <div class="requirement-item">

                                                @if($eligibilityRule->requires_rsbsa)

                                                    <i class="bi bi-check-circle-fill text-success"></i>

                                                    <span>
                                                        RSBSA Required
                                                    </span>

                                                @else

                                                    <i class="bi bi-x-circle text-muted"></i>

                                                    <span class="text-muted">
                                                        RSBSA Not Required
                                                    </span>

                                                @endif

                                            </div>

                                        </div>


                                        {{-- Association --}}
                                        <div class="col-md-4 mb-2">

                                            <div class="requirement-item">

                                                @if($eligibilityRule->requires_association)

                                                    <i class="bi bi-check-circle-fill text-success"></i>

                                                    <span>
                                                        Association Required
                                                    </span>

                                                @else

                                                    <i class="bi bi-x-circle text-muted"></i>

                                                    <span class="text-muted">
                                                        Association Not Required
                                                    </span>

                                                @endif

                                            </div>

                                        </div>


                                        {{-- 4Ps --}}
                                        <div class="col-md-4 mb-2">

                                            <div class="requirement-item">

                                                @if($eligibilityRule->requires_4ps)

                                                    <i class="bi bi-check-circle-fill text-success"></i>

                                                    <span>
                                                        4Ps Required
                                                    </span>

                                                @else

                                                    <i class="bi bi-x-circle text-muted"></i>

                                                    <span class="text-muted">
                                                        4Ps Not Required
                                                    </span>

                                                @endif

                                            </div>

                                        </div>

                                    </div>

                                @else

                                    <div class="text-muted">

                                        <i class="bi bi-info-circle me-1"></i>

                                        No eligibility requirements configured
                                        for this benefit.

                                    </div>

                                @endif

                            </div>

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center py-5">

                    <i class="bi bi-gift fs-1 text-muted"></i>

                    <h6 class="fw-bold mt-3">
                        No Benefits
                    </h6>

                    <p class="text-muted mb-0">
                        This program does not have any configured benefits.
                    </p>

                </div>

            @endforelse

        </div>

    </div>

</div>


{{-- ================================================================
     PROGRAM VIEW CSS
     ================================================================ --}}
<style>

.program-view-icon {

    width: 48px;
    height: 48px;

    border-radius: 12px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #d9f7e5;

    color: #198754;

    font-size: 21px;

}


.program-status {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 5px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;

}


.status-dot {

    width: 7px;
    height: 7px;

    border-radius: 50%;

    display: inline-block;

}


.status-active {

    background: #d9f7e5;
    color: #198754;

}


.status-active .status-dot {

    background: #198754;

}


.status-upcoming {

    background: #fff3cd;
    color: #946c00;

}


.status-upcoming .status-dot {

    background: #ffc107;

}


.status-inactive {

    background: #e9ecef;
    color: #6c757d;

}


.status-inactive .status-dot {

    background: #6c757d;

}


/* ================================================================
   SCHEDULE
   ================================================================ */

.schedule-box {

    display: flex;

    align-items: center;

    gap: 12px;

    padding: 15px;

    border: 1px solid #e9ecef;

    border-radius: 10px;

    background: #fafafa;

}


.schedule-icon {

    width: 38px;
    height: 38px;

    border-radius: 9px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eef7f1;

    color: #198754;

}


/* ================================================================
   BENEFIT
   ================================================================ */

.benefit-total {

    padding: 6px 12px;

    border-radius: 20px;

    background: #e8f1ff;

    color: #0d6efd;

    font-size: 12px;

    font-weight: 600;

}


.benefit-card {

    padding: 20px;

    border: 1px solid #e9ecef;

    border-radius: 12px;

    background: #fff;

}


.benefit-icon {

    width: 42px;
    height: 42px;

    min-width: 42px;

    border-radius: 10px;

    display: flex;
    align-items: center;
    justify-content: center;

    background: #eef7f1;

    color: #198754;

    font-size: 18px;

}


/* ================================================================
   ELIGIBILITY
   ================================================================ */

.eligibility-box {

    padding: 16px;

    border-radius: 10px;

    background: #f8faf9;

    border: 1px solid #e2ebe5;

}


.requirement-badge {

    display: inline-block;

    padding: 5px 9px;

    margin-right: 4px;

    margin-bottom: 4px;

    border-radius: 6px;

    background: #e8f1ff;

    color: #0d6efd;

    font-size: 12px;

    font-weight: 500;

}


.requirement-item {

    display: flex;

    align-items: center;

    gap: 7px;

    font-size: 13px;

}


/* ================================================================
   MOBILE
   ================================================================ */

@media (max-width: 768px) {

    .d-flex.justify-content-between.align-items-center.mb-4 {

        flex-direction: column;

        align-items: flex-start !important;

        gap: 15px;

    }

}

</style>

@endsection