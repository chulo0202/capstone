@extends('layouts.farmer')

@section('page-title', 'Notifications')
@section('page-subtitle', 'Stay updated with your applications, benefits, announcements, and weather alerts')

@section('main')

@php
    $smsCount = $notifications->count();
    $announcementCount = $announcements->count();
    $distributionCount = $distributions->count();
    $weatherCount = $weather ? 1 : 0;

    $totalUpdates = $smsCount + $announcementCount + $distributionCount + $weatherCount;
@endphp


{{-- =========================================================
    SUMMARY CARDS
========================================================= --}}
<div class="row g-3 mb-4">

    {{-- ALL NOTIFICATIONS --}}
    <div class="col-md-6 col-xl-3">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="small text-muted mb-1">
                            All Updates
                        </div>

                        <h3 class="fw-bold mb-0">
                            {{ $totalUpdates }}
                        </h3>

                        <small class="text-muted">
                            Notifications
                        </small>

                    </div>

                    <div
                        class="rounded-circle bg-primary bg-opacity-10
                               d-flex align-items-center justify-content-center"
                        style="width:50px;height:50px;"
                    >
                        <i class="bi bi-bell text-primary fs-4"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- SMS --}}
    <div class="col-md-6 col-xl-3">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="small text-muted mb-1">
                            SMS
                        </div>

                        <h3 class="fw-bold mb-0">
                            {{ $smsCount }}
                        </h3>

                        <small class="text-muted">
                            Messages
                        </small>

                    </div>

                    <div
                        class="rounded-circle bg-info bg-opacity-10
                               d-flex align-items-center justify-content-center"
                        style="width:50px;height:50px;"
                    >
                        <i class="bi bi-chat-left-text text-info fs-4"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- WEATHER --}}
    <div class="col-md-6 col-xl-3">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="small text-muted mb-1">
                            Weather
                        </div>

                        <h3 class="fw-bold mb-0">
                            {{ $weatherCount }}
                        </h3>

                        <small class="text-muted">
                            Weather alert
                        </small>

                    </div>

                    <div
                        class="rounded-circle bg-warning bg-opacity-10
                               d-flex align-items-center justify-content-center"
                        style="width:50px;height:50px;"
                    >
                        <i class="bi bi-cloud-sun text-warning fs-4"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- DISTRIBUTION --}}
    <div class="col-md-6 col-xl-3">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <div class="small text-muted mb-1">
                            Distribution
                        </div>

                        <h3 class="fw-bold mb-0">
                            {{ $distributionCount }}
                        </h3>

                        <small class="text-muted">
                            Benefits received
                        </small>

                    </div>

                    <div
                        class="rounded-circle bg-success bg-opacity-10
                               d-flex align-items-center justify-content-center"
                        style="width:50px;height:50px;"
                    >
                        <i class="bi bi-box-seam text-success fs-4"></i>
                    </div>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
    CATEGORY FILTER
========================================================= --}}
<div class="card border-0 shadow-sm mb-4">

    <div class="card-body py-3">

        <div class="d-flex flex-wrap align-items-center gap-2">

            <span class="small fw-semibold text-muted me-2">
                <i class="bi bi-funnel me-1"></i>
                Filter:
            </span>


            <button
                type="button"
                class="btn btn-sm btn-fams notification-filter active"
                data-filter="all"
            >
                All
            </button>


            <button
                type="button"
                class="btn btn-sm btn-outline-info notification-filter"
                data-filter="sms"
            >
                <i class="bi bi-chat-left-text me-1"></i>
                SMS
            </button>


            <button
                type="button"
                class="btn btn-sm btn-outline-warning notification-filter"
                data-filter="weather"
            >
                <i class="bi bi-cloud-sun me-1"></i>
                Weather
            </button>


            <button
                type="button"
                class="btn btn-sm btn-outline-primary notification-filter"
                data-filter="announcement"
            >
                <i class="bi bi-megaphone me-1"></i>
                Announcements
            </button>


            <button
                type="button"
                class="btn btn-sm btn-outline-success notification-filter"
                data-filter="distribution"
            >
                <i class="bi bi-box-seam me-1"></i>
                Distribution
            </button>

        </div>

    </div>

</div>


{{-- =========================================================
    NOTIFICATION CENTER
========================================================= --}}
<div class="fams-card shadow-sm border-0">

    <div class="fams-card-header">

        <div class="d-flex justify-content-between align-items-center">

            <div>

                <h6 class="mb-1">

                    <i class="bi bi-bell me-2 text-fams"></i>
                    Notification Center

                </h6>

                <small class="text-muted">

                    Your latest messages, announcements, weather information,
                    and distribution updates.

                </small>

            </div>


            <span class="badge bg-light text-dark">

                {{ $totalUpdates }} update(s)

            </span>

        </div>

    </div>


    <div class="fams-card-body p-0">


        {{-- =====================================================
            SMS
        ====================================================== --}}
        <div
            class="notification-section"
            data-category="sms"
        >

            <div class="px-4 py-3 border-bottom bg-light">

                <div class="d-flex align-items-center">

                    <div
                        class="rounded-circle bg-info bg-opacity-10
                               d-flex align-items-center justify-content-center me-3"
                        style="width:42px;height:42px;"
                    >

                        <i class="bi bi-chat-left-text text-info"></i>

                    </div>

                    <div>

                        <div class="fw-semibold">
                            SMS History
                        </div>

                        <small class="text-muted">
                            Messages sent to your registered contact number.
                        </small>

                    </div>

                </div>

            </div>


            @forelse($notifications as $notification)

                <div class="px-4 py-3 border-bottom">

                    <div class="d-flex">

                        <div class="me-3">

                            <div
                                class="rounded-circle bg-info bg-opacity-10
                                       d-flex align-items-center justify-content-center"
                                style="width:38px;height:38px;"
                            >

                                <i class="bi bi-phone text-info"></i>

                            </div>

                        </div>


                        <div class="flex-grow-1">

                            <div class="d-flex justify-content-between">

                                <strong>
                                    {{ $notification->recipient }}
                                </strong>

                                <span class="badge bg-info bg-opacity-10 text-info">
                                    SMS
                                </span>

                            </div>


                            <p class="mb-0 small text-muted mt-1">

                                {{ $notification->message }}

                            </p>

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center text-muted py-4">

                    <i class="bi bi-chat-left-text fs-2 d-block mb-2"></i>

                    <small>No SMS notifications yet.</small>

                </div>

            @endforelse

        </div>


        {{-- =====================================================
            WEATHER
        ====================================================== --}}
        <div
            class="notification-section"
            data-category="weather"
        >

            <div class="px-4 py-3 border-bottom bg-light">

                <div class="d-flex align-items-center">

                    <div
                        class="rounded-circle bg-warning bg-opacity-10
                               d-flex align-items-center justify-content-center me-3"
                        style="width:42px;height:42px;"
                    >

                        <i class="bi bi-cloud-sun text-warning"></i>

                    </div>

                    <div>

                        <div class="fw-semibold">
                            Weather Alerts
                        </div>

                        <small class="text-muted">
                            Current weather information for farmers.
                        </small>

                    </div>

                </div>

            </div>


            <div class="p-4">

                @if($weather)

                    <div
                        class="border rounded p-3
                               bg-warning bg-opacity-10"
                    >

                        <div class="d-flex align-items-start">

                            <div class="me-3">

                                <i class="bi bi-cloud-sun-fill text-warning fs-2"></i>

                            </div>

                            <div>

                                <span class="badge bg-warning text-dark mb-2">
                                    Weather Update
                                </span>

                                <h6 class="fw-bold mb-2">
                                    {{ ucfirst($weather->description) }}
                                </h6>

                                <div class="row g-2">

                                    <div class="col-sm-6">

                                        <small class="text-muted">
                                            Temperature
                                        </small>

                                        <div class="fw-semibold">
                                            {{ $weather->temperature }}°C
                                        </div>

                                    </div>


                                    <div class="col-sm-6">

                                        <small class="text-muted">
                                            Humidity
                                        </small>

                                        <div class="fw-semibold">
                                            {{ $weather->humidity }}%
                                        </div>

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                @else

                    <div class="text-center text-muted py-3">

                        <i class="bi bi-cloud-slash fs-2 d-block mb-2"></i>

                        <small>
                            No weather data available.
                        </small>

                    </div>

                @endif

            </div>

        </div>


        {{-- =====================================================
            ANNOUNCEMENTS
        ====================================================== --}}
        <div
            class="notification-section"
            data-category="announcement"
        >

            <div class="px-4 py-3 border-bottom bg-light">

                <div class="d-flex align-items-center">

                    <div
                        class="rounded-circle bg-primary bg-opacity-10
                               d-flex align-items-center justify-content-center me-3"
                        style="width:42px;height:42px;"
                    >

                        <i class="bi bi-megaphone text-primary"></i>

                    </div>

                    <div>

                        <div class="fw-semibold">
                            Latest Announcements
                        </div>

                        <small class="text-muted">
                            Important updates from the Municipal Agriculture Office.
                        </small>

                    </div>

                </div>

            </div>


            @forelse($announcements as $announcement)

                <div class="px-4 py-3 border-bottom">

                    <div class="d-flex">

                        <div class="me-3">

                            <div
                                class="rounded-circle bg-primary bg-opacity-10
                                       d-flex align-items-center justify-content-center"
                                style="width:38px;height:38px;"
                            >

                                <i class="bi bi-megaphone text-primary"></i>

                            </div>

                        </div>


                        <div class="flex-grow-1">

                            <span class="badge bg-primary mb-2">
                                Announcement
                            </span>

                            <h6 class="fw-semibold mb-1">

                                {{ $announcement->title }}

                            </h6>

                            <p class="small text-muted mb-0">

                                {{ Str::limit($announcement->content, 180) }}

                            </p>

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center text-muted py-4">

                    <i class="bi bi-megaphone fs-2 d-block mb-2"></i>

                    <small>
                        No announcements available.
                    </small>

                </div>

            @endforelse

        </div>


        {{-- =====================================================
            DISTRIBUTION
        ====================================================== --}}
        <div
            class="notification-section"
            data-category="distribution"
        >

            <div class="px-4 py-3 border-bottom bg-light">

                <div class="d-flex align-items-center">

                    <div
                        class="rounded-circle bg-success bg-opacity-10
                               d-flex align-items-center justify-content-center me-3"
                        style="width:42px;height:42px;"
                    >

                        <i class="bi bi-box-seam text-success"></i>

                    </div>

                    <div>

                        <div class="fw-semibold">
                            Distribution Updates
                        </div>

                        <small class="text-muted">
                            Records of assistance benefits you received.
                        </small>

                    </div>

                </div>

            </div>


            @forelse($distributions as $distribution)

                <div class="px-4 py-3 border-bottom">

                    <div class="d-flex">

                        <div class="me-3">

                            <div
                                class="rounded-circle bg-success bg-opacity-10
                                       d-flex align-items-center justify-content-center"
                                style="width:38px;height:38px;"
                            >

                                <i class="bi bi-box-seam text-success"></i>

                            </div>

                        </div>


                        <div class="flex-grow-1">

                            <span class="badge bg-success mb-2">
                                Benefit Received
                            </span>

                            <h6 class="fw-semibold mb-1">

                                {{ $distribution->program->name }}

                            </h6>
                            <div class="small text-muted mb-1">
                                {{ $distribution->benefit?->name ?? 'Legacy program-level benefit' }}
                            </div>

                            <p class="small text-muted mb-0">

                                Released on
                                {{ $distribution->distributed_at->format('M d, Y') }}

                            </p>

                        </div>

                    </div>

                </div>

            @empty

                <div class="text-center text-muted py-4">

                    <i class="bi bi-box-seam fs-2 d-block mb-2"></i>

                    <small>
                        No distribution updates yet.
                    </small>

                </div>

            @endforelse

        </div>


    </div>

</div>


{{-- =========================================================
    FILTER SCRIPT
========================================================= --}}
<script>

document.addEventListener('DOMContentLoaded', function () {

    const filterButtons =
        document.querySelectorAll('.notification-filter');

    const sections =
        document.querySelectorAll('.notification-section');


    filterButtons.forEach(function (button) {

        button.addEventListener('click', function () {

            const filter =
                this.dataset.filter;


            // Remove active state
            filterButtons.forEach(function (btn) {

                btn.classList.remove('active');

            });


            // Add active state
            this.classList.add('active');


            // Show / hide notification sections
            sections.forEach(function (section) {

                const category =
                    section.dataset.category;


                if (
                    filter === 'all' ||
                    filter === category
                ) {

                    section.style.display = '';

                } else {

                    section.style.display = 'none';

                }

            });

        });

    });

});

</script>

@endsection