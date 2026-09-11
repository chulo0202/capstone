@extends('layouts.farmer')

@section('page-title', 'Announcements')
@section('page-subtitle', 'Important updates, schedules, and programs from the Municipal Agriculture Office')

@section('main')

{{-- =========================================================
    FEATURED / LATEST ANNOUNCEMENT
========================================================= --}}

@forelse($announcements as $announcement)

    @if($loop->first)

        <div class="card border-0 shadow-sm mb-4 overflow-hidden">

            <div class="card-body p-4 p-lg-5">

                <div class="row align-items-center">

                    <div class="col-lg-8">

                        {{-- CATEGORY --}}
                        <span class="badge bg-success mb-3">

                            <i class="bi bi-megaphone-fill me-1"></i>
                            Latest Announcement

                        </span>


                        {{-- TITLE --}}
                        <h2 class="fw-bold mb-3">

                            {{ $announcement->title }}

                        </h2>


                        {{-- CONTENT --}}
                        <p class="text-muted mb-3" style="line-height: 1.7;">

                            {{ Str::limit($announcement->content, 220) }}

                        </p>


                        {{-- DATE --}}
                        <div class="small text-muted">

                            <i class="bi bi-calendar3 me-1"></i>

                            Published
                            {{ $announcement->publish_date->format('F d, Y h:i A') }}

                        </div>

                    </div>


                    {{-- ICON --}}
                    <div class="col-lg-4 text-center mt-4 mt-lg-0">

                        <div
                            class="rounded-circle bg-success bg-opacity-10
                                   d-inline-flex align-items-center
                                   justify-content-center"
                            style="width:120px;height:120px;"
                        >

                            <i
                                class="bi bi-megaphone-fill text-success"
                                style="font-size:3.5rem;"
                            ></i>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    @endif

@empty

    <div class="card border-0 shadow-sm">

        <div class="card-body text-center py-5">

            <div
                class="rounded-circle bg-light
                       d-inline-flex align-items-center
                       justify-content-center mb-3"
                style="width:80px;height:80px;"
            >

                <i class="bi bi-megaphone text-muted fs-1"></i>

            </div>

            <h5 class="fw-semibold">
                No Announcements
            </h5>

            <p class="text-muted mb-0">
                There are no announcements available at this time.
            </p>

        </div>

    </div>

@endforelse


{{-- =========================================================
    ANNOUNCEMENT LIST
========================================================= --}}

@if($announcements->count() > 0)

    <div class="d-flex justify-content-between align-items-center mb-3">

        <div>

            <h5 class="fw-bold mb-1">
                Latest Announcements
            </h5>

            <small class="text-muted">
                Updates from the Municipal Agriculture Office
            </small>

        </div>


        <span class="badge bg-light text-dark">

            {{ $announcements->total() }}
            announcement(s)

        </span>

    </div>


    <div class="row g-3">

        @foreach($announcements as $announcement)

            {{-- Skip the first announcement because it is featured above --}}
            @if(!$loop->first)

                <div class="col-md-6">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body p-4">

                            {{-- ICON + DATE --}}
                            <div class="d-flex justify-content-between align-items-start mb-3">

                                <div
                                    class="rounded-circle bg-success bg-opacity-10
                                           d-flex align-items-center
                                           justify-content-center"
                                    style="width:45px;height:45px;"
                                >

                                    <i class="bi bi-megaphone-fill text-success"></i>

                                </div>


                                <span class="small text-muted">

                                    <i class="bi bi-calendar3 me-1"></i>

                                    {{ $announcement->publish_date->format('M d, Y') }}

                                </span>

                            </div>


                            {{-- TITLE --}}
                            <h5 class="fw-bold mb-2">

                                {{ $announcement->title }}

                            </h5>


                            {{-- CONTENT --}}
                            <p
                                class="text-muted small mb-3"
                                style="line-height:1.6;"
                            >

                                {{ Str::limit($announcement->content, 150) }}

                            </p>


                            {{-- FOOTER --}}
                            <div class="d-flex justify-content-between align-items-center">

                                <span class="badge bg-success bg-opacity-10 text-success">

                                    <i class="bi bi-info-circle me-1"></i>
                                    MAO Update

                                </span>


                                <small class="text-muted">

                                    {{ $announcement->publish_date->format('h:i A') }}

                                </small>

                            </div>

                        </div>

                    </div>

                </div>

            @endif

        @endforeach

    </div>


    {{-- =====================================================
        PAGINATION
    ====================================================== --}}

    @if($announcements->hasPages())

        <div class="d-flex justify-content-center mt-4">

            {{ $announcements->links() }}

        </div>

    @endif

@endif

@endsection