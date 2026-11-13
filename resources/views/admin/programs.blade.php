@extends('layouts.admin')

@section('page-title', 'Program List')

@section('main')

<div class="container-fluid py-4">

    {{-- ============================================================
         PAGE HEADER
         ============================================================ --}}
    <div class="d-flex justify-content-between align-items-start mb-4">

        <div>

            <div class="d-flex align-items-center gap-2 mb-1">

                <i class="bi bi-journal-text text-success fs-3"></i>

                <h2 class="fw-bold mb-0">
                    Programs List
                </h2>

            </div>

            <p class="text-muted mb-0">
                View all configured farmer assistance programs.
            </p>

        </div>


        {{-- Information --}}
        <div class="program-info-alert d-none d-md-flex align-items-center gap-2">

            <i class="bi bi-info-circle-fill"></i>

            <span>
                Active programs are shown first.
            </span>

        </div>

    </div>


    {{-- ============================================================
         STATUS FILTERS
         ============================================================ --}}
    <div class="card border-0 shadow-sm mb-4">

        <div class="card-body p-3">

            <div class="d-flex flex-wrap gap-2">

                {{-- All --}}
                <a
                    href="{{ route('admin.programs.index') }}"
                    class="program-filter-btn
                    {{ request('status') === null
                        ? 'active-all'
                        : '' }}"
                >
                    <i class="bi bi-grid me-1"></i>
                    All
                </a>


                {{-- Upcoming --}}
                <a
                    href="{{ route('admin.programs.index', ['status' => 'upcoming']) }}"
                    class="program-filter-btn upcoming-filter
                    {{ request('status') === 'upcoming'
                        ? 'active-upcoming'
                        : '' }}"
                >
                    <i class="bi bi-calendar-event me-1"></i>
                    Upcoming
                </a>


                {{-- Active --}}
                <a
                    href="{{ route('admin.programs.index', ['status' => 'active']) }}"
                    class="program-filter-btn active-filter
                    {{ request('status') === 'active'
                        ? 'active-active'
                        : '' }}"
                >
                    <i class="bi bi-play-fill me-1"></i>
                    Active
                </a>


                {{-- Inactive --}}
                <a
                    href="{{ route('admin.programs.index', ['status' => 'inactive']) }}"
                    class="program-filter-btn inactive-filter
                    {{ request('status') === 'inactive'
                        ? 'active-inactive'
                        : '' }}"
                >
                    <i class="bi bi-pause-fill me-1"></i>
                    Inactive
                </a>

                <a
                    href="{{ route('admin.programs.index', ['status' => 'archived']) }}"
                    class="program-filter-btn inactive-filter
                    {{ request('status') === 'archived' ? 'active-inactive' : '' }}"
                >
                    <i class="bi bi-archive me-1"></i>
                    Archived
                </a>

            </div>

        </div>

    </div>


    {{-- ============================================================
         PROGRAM TABLE
         ============================================================ --}}
    <div class="card border-0 shadow-sm program-table-card">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h5 class="fw-bold mb-1">
                        Programs
                    </h5>

                    <small class="text-muted">
                        Active programs appear at the top of the list.
                    </small>

                </div>

                <i class="bi bi-list-ul text-muted fs-4"></i>

            </div>

        </div>


        <div class="card-body p-0">

            <div class="table-responsive">

                <table class="table program-table align-middle mb-0">

                    <thead>

                        <tr>

                            <th class="px-4">
                                PROGRAM
                            </th>

                            <th>
                                BENEFITS
                            </th>

                            <th>
                                START
                            </th>

                            <th>
                                END
                            </th>

                            <th>
                                STATUS
                            </th>

                            <th class="text-center">
                                ACTION
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @forelse($programs as $program)

                            @php

                                $now = now();

                                if ($program->archived_at) {

                                    $status = 'archived';
                                    $statusLabel = 'Archived';
                                    $statusClass = 'status-inactive';

                                } elseif (!$program->is_active) {

                                    $status = 'inactive';
                                    $statusLabel = 'Inactive';
                                    $statusClass = 'status-inactive';

                                } elseif (
                                    $program->start_at &&
                                    $now->lt($program->start_at)
                                ) {

                                    $status = 'upcoming';

                                    $statusLabel = 'Upcoming';

                                    $statusClass =
                                        'status-upcoming';

                                } elseif (
                                    $program->start_at &&
                                    $program->end_at &&
                                    $now->between(
                                        $program->start_at,
                                        $program->end_at
                                    )
                                ) {

                                    $status = 'active';

                                    $statusLabel = 'Active';

                                    $statusClass =
                                        'status-active';

                                } else {

                                    $status = 'inactive';

                                    $statusLabel = 'Inactive';

                                    $statusClass =
                                        'status-inactive';

                                }

                            @endphp


                            <tr
                                class="
                                    program-row
                                    {{ $status === 'active'
                                        ? 'active-program-row'
                                        : '' }}
                                "
                            >

                                {{-- =================================================
                                     PROGRAM
                                     ================================================= --}}
                                <td class="px-4">

                                    <div class="d-flex align-items-center gap-3">

                                        <div
                                            class="
                                                program-icon
                                                {{ $status === 'active'
                                                    ? 'program-icon-active'
                                                    : '' }}
                                            "
                                        >

                                            @if($status === 'active')

                                                <i class="bi bi-shield-check"></i>

                                            @elseif($status === 'upcoming')

                                                <i class="bi bi-calendar-event"></i>

                                            @else

                                                <i class="bi bi-journal-text"></i>

                                            @endif

                                        </div>


                                        <div>

                                            <div class="program-name">

                                                {{ $program->name }}

                                            </div>


                                            @if($status === 'active')

                                                <div class="active-program-label">

                                                    <i class="bi bi-circle-fill"></i>

                                                    Currently Active

                                                </div>

                                            @endif

                                        </div>

                                    </div>

                                </td>


                                {{-- =================================================
                                     BENEFITS COUNT
                                     ================================================= --}}
                                <td>

                                    <div class="d-flex align-items-center gap-2">

                                        <span class="benefit-count">

                                            {{ $program->benefits->count() }}

                                        </span>

                                        <small class="text-muted">
                                            benefits
                                        </small>

                                    </div>

                                </td>


                                {{-- =================================================
                                     START
                                     ================================================= --}}
                                <td>

                                    @if($program->start_at)

                                        <div class="date-main">

                                            <i class="bi bi-calendar3 me-1 text-muted"></i>

                                            {{ $program->start_at->format('M d, Y') }}

                                        </div>

                                        <div class="date-time">

                                            {{ $program->start_at->format('h:i A') }}

                                        </div>

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- =================================================
                                     END
                                     ================================================= --}}
                                <td>

                                    @if($program->end_at)

                                        <div class="date-main">

                                            <i class="bi bi-calendar3 me-1 text-muted"></i>

                                            {{ $program->end_at->format('M d, Y') }}

                                        </div>

                                        <div class="date-time">

                                            {{ $program->end_at->format('h:i A') }}

                                        </div>

                                    @else

                                        <span class="text-muted">
                                            —
                                        </span>

                                    @endif

                                </td>


                                {{-- =================================================
                                     STATUS
                                     ================================================= --}}
                                <td>

                                    <span class="program-status {{ $statusClass }}">

                                        <span class="status-dot"></span>

                                        {{ $statusLabel }}

                                    </span>

                                </td>


                                {{-- =================================================
                                     ACTION
                                     ================================================= --}}
                                <td class="text-center">

                                    <a
                                        href="{{ route(
                                            'admin.programs.show',
                                            $program
                                        ) }}"
                                        class="btn btn-view-program"
                                    >

                                        <i class="bi bi-eye me-1"></i>

                                        View

                                    </a>

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="text-center py-5"
                                >

                                    <div class="empty-programs">

                                        <i
                                            class="
                                                bi bi-journal-x
                                                fs-1
                                                d-block
                                                mb-3
                                            "
                                        ></i>

                                        <h6 class="fw-bold">
                                            No programs found
                                        </h6>

                                        <small>
                                            There are no programs
                                            matching the selected filter.
                                        </small>

                                    </div>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>


        {{-- ============================================================
             PAGINATION
             ============================================================ --}}
        @if($programs->hasPages())

            <div class="card-footer bg-white py-3">

                {{ $programs->withQueryString()->links() }}

            </div>

        @endif

    </div>

</div>


{{-- ================================================================
     PROGRAM LIST CSS
     ================================================================ --}}
<style>

.program-info-alert {

    padding: 10px 16px;

    border: 1px solid #b7e4c7;

    border-radius: 10px;

    background: #f1fff6;

    color: #198754;

    font-size: 13px;

}


/* ================================================================
   FILTER BUTTONS
   ================================================================ */

.program-filter-btn {

    display: inline-flex;

    align-items: center;

    padding: 9px 18px;

    border-radius: 9px;

    text-decoration: none;

    font-weight: 500;

    font-size: 14px;

    border: 1px solid #dee2e6;

    color: #6c757d;

    background: #fff;

    transition: all .2s ease;

}


.program-filter-btn:hover {

    transform: translateY(-1px);

    text-decoration: none;

}


.active-all {

    background: #198754;

    border-color: #198754;

    color: #fff;

}


.active-all:hover {

    color: #fff;

}


.upcoming-filter {

    color: #d39e00;

    border-color: #f3d36a;

}


.active-upcoming {

    background: #ffc107;

    border-color: #ffc107;

    color: #212529;

}


.active-filter {

    color: #198754;

    border-color: #a8dfc1;

}


.active-active {

    background: #198754;

    border-color: #198754;

    color: #fff;

}


.active-active:hover {

    color: #fff;

}


.inactive-filter {

    color: #6c757d;

    border-color: #ced4da;

}


.active-inactive {

    background: #6c757d;

    border-color: #6c757d;

    color: #fff;

}


/* ================================================================
   TABLE
   ================================================================ */

.program-table-card {

    overflow: hidden;

}


.program-table {

    min-width: 900px;

}


.program-table thead th {

    background: #f8f9fa;

    color: #6c757d;

    font-size: 12px;

    font-weight: 700;

    letter-spacing: .04em;

    padding: 15px 14px;

    border-bottom: 1px solid #dee2e6;

    white-space: nowrap;

}


.program-table tbody td {

    padding: 14px;

    border-bottom: 1px solid #edf0f2;

}


.program-row {

    transition: background-color .2s ease;

}


.program-row:hover {

    background: #f8fbf9;

}


/* ================================================================
   ACTIVE PROGRAM
   ================================================================ */

.active-program-row {

    background: #f1fff7;

    box-shadow:
        inset 4px 0 0 #198754;

}


.active-program-row:hover {

    background: #eafff2;

}


.program-icon {

    width: 42px;

    height: 42px;

    min-width: 42px;

    border-radius: 11px;

    display: flex;

    align-items: center;

    justify-content: center;

    background: #eef1f4;

    color: #6c757d;

    font-size: 18px;

}


.program-icon-active {

    background: #d9f7e5;

    color: #198754;

}


.program-name {

    font-weight: 600;

    color: #172033;

}


.active-program-label {

    margin-top: 3px;

    color: #198754;

    font-size: 11px;

    font-weight: 600;

}


.active-program-label i {

    font-size: 6px;

    vertical-align: middle;

    margin-right: 4px;

}


/* ================================================================
   BENEFITS
   ================================================================ */

.benefit-count {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    min-width: 30px;

    height: 30px;

    padding: 0 8px;

    border-radius: 8px;

    background: #e8f1ff;

    color: #0d6efd;

    font-weight: 700;

    font-size: 13px;

}


/* ================================================================
   DATE / TIME
   ================================================================ */

.date-main {

    font-size: 13px;

    font-weight: 500;

    white-space: nowrap;

}


.date-time {

    margin-left: 20px;

    margin-top: 2px;

    color: #8a9199;

    font-size: 12px;

}


/* ================================================================
   STATUS
   ================================================================ */

.program-status {

    display: inline-flex;

    align-items: center;

    gap: 6px;

    padding: 6px 11px;

    border-radius: 20px;

    font-size: 12px;

    font-weight: 600;

    white-space: nowrap;

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
   VIEW BUTTON
   ================================================================ */

.btn-view-program {

    border: 1px solid #0d6efd;

    color: #0d6efd;

    background: #fff;

    border-radius: 7px;

    padding: 6px 13px;

    font-size: 13px;

    font-weight: 500;

    transition: all .2s ease;

}


.btn-view-program:hover {

    background: #0d6efd;

    color: #fff;

}


/* ================================================================
   EMPTY STATE
   ================================================================ */

.empty-programs {

    color: #8a9199;

}


.empty-programs i {

    color: #adb5bd;

}


/* ================================================================
   MOBILE
   ================================================================ */

@media (max-width: 768px) {

    .program-info-alert {

        display: none !important;

    }

    .program-filter-btn {

        padding: 8px 13px;

    }

}

</style>

@endsection