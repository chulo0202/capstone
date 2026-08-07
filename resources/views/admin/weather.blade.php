@extends('layouts.admin')

@section('page-title', 'Weather Monitoring')

@section('main')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">Weather Monitoring & Advisories</h5>

    <form method="POST" action="{{ route('admin.weather.refresh') }}">
        @csrf
        <button class="btn btn-fams">
            <i class="bi bi-arrow-clockwise me-1"></i>
            Refresh Weather
        </button>
    </form>
</div>

@if($current)

<div class="row g-3 mb-4">

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                @if($current->weather_icon)
                    <img src="https://openweathermap.org/img/wn/{{ $current->weather_icon }}@2x.png" width="70">
                @endif

                <h3>{{ $current->temperature }}°C</h3>
                <small class="text-muted">
                    {{ ucfirst($current->description) }}
                </small>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Humidity</p>
                <h4>{{ $current->humidity }}%</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Wind Speed</p>
                <h4>{{ $current->wind_speed }} m/s</h4>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Pressure</p>
                <h4>{{ $current->pressure }} hPa</h4>
            </div>
        </div>
    </div>

</div>

<div class="row g-3 mb-4">

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Rainfall</p>
                <h4>{{ $current->rainfall }} mm</h4>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Cloud Cover</p>
                <h4>{{ $current->clouds }}%</h4>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card shadow-sm border-0">
            <div class="card-body text-center">
                <p class="text-muted mb-1">Alert Level</p>

                @php
                    $badge = match($current->alert_level){
                        'Storm Warning' => 'danger',
                        'Rain Alert' => 'warning',
                        'Extreme Heat' => 'danger',
                        'Strong Wind' => 'primary',
                        default => 'success',
                    };
                @endphp

                <span class="badge bg-{{ $badge }} fs-6">
                    {{ $current->alert_level }}
                </span>

            </div>
        </div>
    </div>

</div>

@if($current->alert_level != 'Normal')

<div class="alert alert-{{ $badge }}">
    <strong>{{ $current->alert_level }}</strong>

    <hr>

    {{ $current->advisory }}
</div>

@endif

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">
            Predictive Advisory
        </h6>
    </div>

    <div class="card-body">

        {{ $current->advisory }}

    </div>
</div>

@else

<div class="alert alert-info">

    No weather data available.

</div>

@endif


<div class="card shadow-sm border-0">

    <div class="card-header bg-white">

        <h6 class="mb-0">
            Weather History
        </h6>

    </div>

    <div class="table-responsive">

        <table class="table table-hover mb-0">

            <thead class="table-light">

            <tr>

                <th>Date</th>
                <th>Location</th>
                <th>Temp</th>
                <th>Humidity</th>
                <th>Pressure</th>
                <th>Rain</th>
                <th>Alert</th>

            </tr>

            </thead>

            <tbody>

            @forelse($history as $weather)

                <tr>

                    <td>{{ $weather->fetched_at->format('M d, Y h:i A') }}</td>

                    <td>{{ $weather->location }}</td>

                    <td>{{ $weather->temperature }}°C</td>

                    <td>{{ $weather->humidity }}%</td>

                    <td>{{ $weather->pressure }}</td>

                    <td>{{ $weather->rainfall }} mm</td>

                    <td>

                        <span class="badge bg-secondary">

                            {{ $weather->alert_level }}

                        </span>

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="7" class="text-center">

                        No weather history available.

                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>
<div class="card shadow-sm border-0 mt-4">

    <div class="card-header bg-white">
        <h6 class="mb-0">
            <i class="bi bi-people-fill text-danger me-2"></i>
            At-Risk Farmers
        </h6>
    </div>

    <div class="table-responsive">

        <table class="table table-hover mb-0">

            <thead class="table-light">

                <tr>

                    <th>Farmer</th>
                    <th>Barangay</th>
                    <th>Crop</th>
                    <th>Reason</th>
                    <th>Recommendation</th>

                </tr>

            </thead>

            <tbody>

            @forelse($atRiskFarmers as $risk)

                <tr>

                    <td>
                        {{ $risk['farmer']->full_name }}
                    </td>

                    <td>
                        {{ $risk['farmer']->barangay }}
                    </td>

                    <td>
                        {{ $risk['farmer']->crop_type }}
                    </td>

                    <td>

                        <span class="badge bg-danger">

                            {{ $risk['reason'] }}

                        </span>

                    </td>

                    <td>

                        {{ $risk['recommendation'] }}

                    </td>

                </tr>

            @empty

                <tr>

                    <td colspan="5" class="text-center text-muted">

                        No at-risk farmers.

                    </td>

                </tr>

            @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection