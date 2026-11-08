@extends('layouts.admin')

@section('page-title', 'Weather Monitoring')

@section('main')

{{-- =========================================================
     WEATHER PAGE HEADER
========================================================= --}}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">

    <div>
        <h4 class="fw-bold mb-1">
            Weather Monitoring
        </h4>

        <p class="text-muted mb-0">
            Monitor current weather conditions and farming advisories
        </p>
    </div>

    <form method="POST" action="{{ route('admin.weather.refresh') }}">
        @csrf

        <button type="submit" class="btn btn-fams px-4">
            <i class="bi bi-arrow-clockwise me-2"></i>
            Refresh Weather
        </button>
    </form>

</div>


@if($current)

{{-- =========================================================
     CURRENT WEATHER OVERVIEW
========================================================= --}}
<div class="row g-3 mb-4">

    {{-- TEMPERATURE --}}
    <div class="col-xl-3 col-md-6">

        <div class="card border-0 shadow-sm h-100 weather-card">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start mb-3">

                    <div>
                        <p class="text-muted small mb-1">
                            Temperature
                        </p>

                        <h2 class="fw-bold mb-0">
                            {{ $current->temperature }}°C
                        </h2>
                    </div>

                    <div class="weather-icon-box">
                        @if($current->weather_icon)
                            <img
                                src="https://openweathermap.org/img/wn/{{ $current->weather_icon }}@2x.png"
                                alt="Weather"
                                width="55"
                            >
                        @else
                            <i class="bi bi-cloud-sun fs-2"></i>
                        @endif
                    </div>

                </div>

                <div class="small text-muted">
                    <i class="bi bi-cloud me-1"></i>
                    {{ ucfirst($current->description) }}
                </div>

            </div>

        </div>

    </div>


    {{-- HUMIDITY --}}
    <div class="col-xl-3 col-md-6">

        <div class="card border-0 shadow-sm h-100 weather-card">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted small mb-1">
                            Humidity
                        </p>

                        <h2 class="fw-bold mb-0">
                            {{ $current->humidity }}%
                        </h2>

                    </div>

                    <div class="weather-metric-icon">
                        <i class="bi bi-droplet"></i>
                    </div>

                </div>

                <div class="small text-muted mt-3">
                    <i class="bi bi-water me-1"></i>
                    Atmospheric moisture
                </div>

            </div>

        </div>

    </div>


    {{-- WIND SPEED --}}
    <div class="col-xl-3 col-md-6">

        <div class="card border-0 shadow-sm h-100 weather-card">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted small mb-1">
                            Wind Speed
                        </p>

                        <h2 class="fw-bold mb-0">
                            {{ $current->wind_speed }}
                            <span class="fs-6 fw-normal text-muted">m/s</span>
                        </h2>

                    </div>

                    <div class="weather-metric-icon">
                        <i class="bi bi-wind"></i>
                    </div>

                </div>

                <div class="small text-muted mt-3">
                    <i class="bi bi-arrow-right me-1"></i>
                    Current wind condition
                </div>

            </div>

        </div>

    </div>


    {{-- PRESSURE --}}
    <div class="col-xl-3 col-md-6">

        <div class="card border-0 shadow-sm h-100 weather-card">

            <div class="card-body p-4">

                <div class="d-flex justify-content-between align-items-start">

                    <div>

                        <p class="text-muted small mb-1">
                            Pressure
                        </p>

                        <h2 class="fw-bold mb-0">
                            {{ $current->pressure }}
                            <span class="fs-6 fw-normal text-muted">hPa</span>
                        </h2>

                    </div>

                    <div class="weather-metric-icon">
                        <i class="bi bi-speedometer2"></i>
                    </div>

                </div>

                <div class="small text-muted mt-3">
                    <i class="bi bi-bar-chart me-1"></i>
                    Atmospheric pressure
                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     SECONDARY WEATHER METRICS
========================================================= --}}
<div class="row g-3 mb-4">

    {{-- RAINFALL --}}
    <div class="col-lg-4">

        <div class="card border-0 shadow-sm h-100 weather-card">

            <div class="card-body p-4">

                <div class="d-flex align-items-center gap-3">

                    <div class="weather-metric-icon">
                        <i class="bi bi-cloud-rain"></i>
                    </div>

                    <div>

                        <p class="text-muted small mb-1">
                            Rainfall
                        </p>

                        <h4 class="fw-bold mb-0">
                            {{ $current->rainfall }} mm
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- CLOUD COVER --}}
    <div class="col-lg-4">

        <div class="card border-0 shadow-sm h-100 weather-card">

            <div class="card-body p-4">

                <div class="d-flex align-items-center gap-3">

                    <div class="weather-metric-icon">
                        <i class="bi bi-clouds"></i>
                    </div>

                    <div>

                        <p class="text-muted small mb-1">
                            Cloud Cover
                        </p>

                        <h4 class="fw-bold mb-0">
                            {{ $current->clouds }}%
                        </h4>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ALERT LEVEL --}}
    <div class="col-lg-4">

        <div class="card border-0 shadow-sm h-100 weather-card">

            <div class="card-body p-4">

                @php
                    $badge = match($current->alert_level){
                        'Storm Warning' => 'danger',
                        'Rain Alert' => 'warning',
                        'Extreme Heat' => 'danger',
                        'Strong Wind' => 'primary',
                        default => 'success',
                    };
                @endphp

                <div class="d-flex justify-content-between align-items-center">

                    <div>

                        <p class="text-muted small mb-1">
                            Current Alert Level
                        </p>

                        <h5 class="fw-bold mb-0">
                            Weather Status
                        </h5>

                    </div>

                    <span class="badge bg-{{ $badge }} fs-6 px-3 py-2">
                        <i class="bi bi-shield-exclamation me-1"></i>
                        {{ $current->alert_level }}
                    </span>

                </div>

            </div>

        </div>

    </div>

</div>


{{-- =========================================================
     WEATHER ALERT
========================================================= --}}
@if($current->alert_level != 'Normal')

<div class="alert alert-{{ $badge }} border-0 shadow-sm mb-4">

    <div class="d-flex gap-3">

        <div class="fs-4">
            <i class="bi bi-exclamation-triangle-fill"></i>
        </div>

        <div>

            <h6 class="fw-bold mb-1">
                {{ $current->alert_level }}
            </h6>

            <p class="mb-0">
                {{ $current->advisory }}
            </p>

        </div>

    </div>

</div>

@endif


{{-- =========================================================
     PREDICTIVE ADVISORY
========================================================= --}}
<div class="card border-0 shadow-sm mb-4">

    <div class="card-header bg-white border-bottom py-3 px-4">

        <div class="d-flex align-items-center gap-2">

            <div class="section-icon">
                <i class="bi bi-lightbulb"></i>
            </div>

            <div>

                <h6 class="fw-bold mb-0">
                    Predictive Advisory
                </h6>

                <small class="text-muted">
                    Farming guidance based on current weather conditions
                </small>

            </div>

        </div>

    </div>

    <div class="card-body p-4">

        <div class="advisory-box">

            <i class="bi bi-info-circle-fill me-2"></i>

            <span>
                {{ $current->advisory }}
            </span>

        </div>

    </div>

</div>


{{-- =========================================================
     WEATHER HISTORY
========================================================= --}}
<div class="card border-0 shadow-sm mb-4 weather-history-card">

    {{-- HEADER --}}
    <div class="card-header bg-white border-bottom py-3 px-4">

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">

            <div class="d-flex align-items-center gap-2">

                <div class="section-icon">
                    <i class="bi bi-clock-history"></i>
                </div>

                <div>

                    <h6 class="fw-bold mb-0">
                        Weather History
                    </h6>

                    <small class="text-muted">
                        Previously recorded weather conditions
                    </small>

                </div>

            </div>

            <span class="weather-history-count">
                <i class="bi bi-list-ul me-1"></i>
                Latest 5 Records
            </span>

        </div>

    </div>


    {{-- TABLE --}}
    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table align-middle mb-0 weather-history-table">

                <thead>

                    <tr>

                        <th class="ps-4">
                            Date
                        </th>

                        <th>
                            Location
                        </th>

                        <th>
                            Temperature
                        </th>

                        <th>
                            Humidity
                        </th>

                        <th>
                            Pressure
                        </th>

                        <th>
                            Rainfall
                        </th>

                        <th>
                            Alert
                        </th>

                    </tr>

                </thead>


                <tbody>

                @forelse($history->take(5) as $weather)

                    <tr>

                        {{-- DATE --}}
                        <td class="ps-4">

                            <div class="history-date">
                                {{ $weather->fetched_at->format('M d, Y') }}
                            </div>

                            <div class="history-time">
                                {{ $weather->fetched_at->format('h:i A') }}
                            </div>

                        </td>


                        {{-- LOCATION --}}
                        <td>

                            <div class="history-location">

                                <i class="bi bi-geo-alt"></i>

                                <span>
                                    {{ $weather->location }}
                                </span>

                            </div>

                        </td>


                        {{-- TEMPERATURE --}}
                        <td>

                            <span class="history-temperature">
                                {{ number_format($weather->temperature, 2) }}°C
                            </span>

                        </td>


                        {{-- HUMIDITY --}}
                        <td>

                            <span class="history-value">
                                {{ $weather->humidity }}%
                            </span>

                        </td>


                        {{-- PRESSURE --}}
                        <td>

                            <span class="history-value">
                                {{ number_format($weather->pressure, 2) }} hPa
                            </span>

                        </td>


                        {{-- RAINFALL --}}
                        <td>

                            <span class="history-value">
                                {{ number_format($weather->rainfall, 2) }} mm
                            </span>

                        </td>


                        {{-- ALERT --}}
                        <td>

                            @php
                                $historyBadge = match($weather->alert_level) {
                                    'Storm Warning' => 'danger',
                                    'Rain Alert' => 'warning text-dark',
                                    'Extreme Heat' => 'danger',
                                    'Strong Wind' => 'primary',
                                    default => 'success',
                                };
                            @endphp

                            <span class="badge bg-{{ $historyBadge }} history-alert-badge">

                                @if($weather->alert_level === 'Normal')
                                    <i class="bi bi-check-circle me-1"></i>
                                @elseif($weather->alert_level === 'Rain Alert')
                                    <i class="bi bi-cloud-rain me-1"></i>
                                @elseif($weather->alert_level === 'Storm Warning')
                                    <i class="bi bi-cloud-lightning-rain me-1"></i>
                                @elseif($weather->alert_level === 'Extreme Heat')
                                    <i class="bi bi-thermometer-high me-1"></i>
                                @else
                                    <i class="bi bi-wind me-1"></i>
                                @endif

                                {{ $weather->alert_level }}

                            </span>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="7" class="text-center py-5">

                            <div class="weather-history-empty">

                                <i class="bi bi-cloud-slash"></i>

                                <div class="fw-semibold">
                                    No Weather History
                                </div>

                                <small>
                                    No previously recorded weather conditions are available.
                                </small>

                            </div>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- FOOTER --}}
    @if($history->count() > 5)

        <div class="weather-history-footer">

            <span>
                <i class="bi bi-info-circle me-1"></i>
                Showing the 5 most recent weather records.
            </span>

        </div>

    @endif

</div>


{{-- =========================================================
     AT-RISK FARMERS
========================================================= --}}
<div class="card border-0 shadow-sm">

    <div class="card-header bg-white border-bottom py-3 px-4">

        <div class="d-flex align-items-center gap-2">

            <div class="section-icon section-icon-danger">
                <i class="bi bi-people-fill"></i>
            </div>

            <div>

                <h6 class="fw-bold mb-0">
                    At-Risk Farmers
                </h6>

                <small class="text-muted">
                    Farmers potentially affected by current weather conditions
                </small>

            </div>

        </div>

    </div>

    <div class="card-body p-0">

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0 weather-table">

                <thead>

                    <tr>

                        <th class="ps-4">
                            Farmer
                        </th>

                        <th>
                            Barangay
                        </th>

                        <th>
                            Crop
                        </th>

                        <th>
                            Reason
                        </th>

                        <th>
                            Recommendation
                        </th>

                    </tr>

                </thead>

                <tbody>

                @forelse($atRiskFarmers as $risk)

                    <tr>

                        <td class="ps-4">

                            <div class="d-flex align-items-center gap-2">

                                <div class="farmer-icon">
                                    <i class="bi bi-person"></i>
                                </div>

                                <strong>
                                    {{ $risk['farmer']->full_name }}
                                </strong>

                            </div>

                        </td>

                        <td>

                            <span class="text-muted">
                                <i class="bi bi-geo-alt me-1"></i>
                                {{ $risk['farmer']->barangay }}
                            </span>

                        </td>

                        <td>

                            <span class="badge bg-light text-dark border">
                                {{ $risk['farmer']->crop_type }}
                            </span>

                        </td>

                        <td>

                            <span class="badge bg-danger">
                                <i class="bi bi-exclamation-circle me-1"></i>
                                {{ $risk['reason'] }}
                            </span>

                        </td>

                        <td>

                            <span class="small">
                                {{ $risk['recommendation'] }}
                            </span>

                        </td>

                    </tr>

                @empty

                    <tr>

                        <td colspan="5" class="text-center py-5">

                            <div class="text-muted">

                                <i class="bi bi-shield-check fs-1 d-block mb-2"></i>

                                No at-risk farmers.

                            </div>

                        </td>

                    </tr>

                @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>


@else

{{-- =========================================================
     NO WEATHER DATA
========================================================= --}}
<div class="card border-0 shadow-sm">

    <div class="card-body text-center py-5">

        <div class="empty-weather-icon mb-3">
            <i class="bi bi-cloud-slash"></i>
        </div>

        <h5 class="fw-bold">
            No Weather Data Available
        </h5>

        <p class="text-muted mb-4">
            Weather information is currently unavailable.
        </p>

        <form method="POST" action="{{ route('admin.weather.refresh') }}">
            @csrf

            <button type="submit" class="btn btn-fams px-4">

                <i class="bi bi-arrow-clockwise me-2"></i>

                Refresh Weather

            </button>

        </form>

    </div>

</div>

@endif
@endsection