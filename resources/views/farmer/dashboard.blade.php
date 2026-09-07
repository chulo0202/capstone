@extends('layouts.farmer')

@section('page-title', 'Dashboard')
@section('page-subtitle', 'Your assistance overview and latest updates')

@section('main')

@php
    // Presentation-only helpers — no new data, no controller changes.
    $farmerDisplayName = $farmer?->full_name ?? auth()->user()->name;
    $firstName = trim(explode(' ', $farmerDisplayName)[0] ?? $farmerDisplayName);
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
@endphp

<style>
    /* ---- FAMS Dashboard (scoped, additive styles) ---- */
    .crop-icon {
    width: 64px;
    height: 64px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.crop-icon i {
    font-size: 30px;
}
    
    .fams-dash-hero {
        position: relative;
        overflow: hidden;
        background: linear-gradient(135deg, #2E7D32 0%, #4CAF50 100%);
        border-radius: 20px;
        padding: 2rem 2.25rem;
        color: #fff;
        margin-bottom: 1.75rem;
        box-shadow: 0 10px 28px rgba(27, 94, 32, .18);
    }

    .fams-dash-hero::before {
        content: "";
        position: absolute;
        top: -70px;
        right: -60px;
        width: 220px;
        height: 220px;
        background: radial-gradient(circle, rgba(255,255,255,.16) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
    }

    .fams-dash-hero::after {
        content: "";
        position: absolute;
        bottom: -90px;
        left: -40px;
        width: 200px;
        height: 200px;
        background: radial-gradient(circle, rgba(255,193,7,.14) 0%, rgba(255,193,7,0) 70%);
        border-radius: 50%;
    }

    .fams-dash-hero-inner {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.5rem;
        flex-wrap: wrap;
    }

    .fams-dash-hero-greeting {
        font-size: .82rem;
        font-weight: 600;
        letter-spacing: .06em;
        text-transform: uppercase;
        opacity: .85;
        margin-bottom: .35rem;
    }

    .fams-dash-hero-title {
        font-size: clamp(1.35rem, 2.4vw, 1.9rem);
        font-weight: 800;
        margin-bottom: .5rem;
        line-height: 1.25;
    }

    .fams-dash-hero-sub {
        font-size: .92rem;
        opacity: .92;
        max-width: 480px;
        margin-bottom: 0;
    }

    .fams-crop-icon {
    width: 58px;
    height: 58px;
    border-radius: 18px;
    font-size: 1.6rem;
    background: rgba(47, 125, 85, 0.12);
    border: 1px solid rgba(47, 125, 85, 0.20);
    color: #2f7d55;
    backdrop-filter: blur(6px);
}

    .fams-dash-hero-icon {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.18);
    border: 2px solid rgba(255, 255, 255, 0.30);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2.6rem;
    flex-shrink: 0;
    backdrop-filter: blur(8px);
    box-shadow:
        0 10px 25px rgba(0, 0, 0, 0.12),
        inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

    /* Alert */
    .fams-dash-alert-wrap { border-radius: 14px; overflow: hidden; }

    /* Stat cards */
    .fams-dash-stat-card {
        border-radius: 16px !important;
        box-shadow: 0 4px 14px rgba(27, 94, 32, .07) !important;
        transition: transform .25s ease, box-shadow .25s ease;
        border: 1px solid #eef2ef;
        height: 100%;
    }

    .fams-dash-stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 14px 28px rgba(27, 94, 32, .14) !important;
    }

    .fams-dash-stat-icon-ring {
        width: 50px;
        height: 50px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.35rem;
    }

    /* Section cards */
    .fams-dash-card {
        border-radius: 16px !important;
        box-shadow: 0 4px 16px rgba(27, 94, 32, .06) !important;
        border: 1px solid #eef2ef;
        overflow: hidden;
        height: 100%;
        transition: box-shadow .25s ease;
    }
    .fams-dash-card:hover { box-shadow: 0 10px 24px rgba(27, 94, 32, .1) !important; }

    .fams-dash-card .fams-card-header {
        background: #F7FAF7;
        border-bottom: 1px solid #eef2ef;
    }

    /* List rows */
    .fams-dash-list-item {
        transition: background-color .18s ease;
        border-bottom: 1px solid #f1f4f2;
    }
    .fams-dash-list-item:last-child { border-bottom: none; }
    .fams-dash-list-item:hover { background-color: #F7FAF7; }

    .fams-dash-row-icon {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 11px;
        background: #E8F5E9;
        color: #2E7D32;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
    }

    .fams-dash-status-pill {
        display: inline-flex;
        align-items: center;
        gap: .3rem;
        font-weight: 600;
        font-size: .76rem;
        padding: .35rem .75rem;
        border-radius: 999px !important;
    }

    .fams-dash-empty {
        text-align: center;
        padding: 2.25rem 1rem;
        color: #9aa39c;
    }
    .fams-dash-empty i { font-size: 1.8rem; display: block; margin-bottom: .5rem; color: #c3ccc5; }

    /* Weather */
    .fams-dash-weather-hero {
        text-align: center;
        padding: 1rem 0 1.35rem;
        border-bottom: 1px solid #f1f4f2;
        margin-bottom: 1.1rem;
    }

    .fams-dash-weather-temp {
        font-size: 2.5rem;
        font-weight: 800;
        color: #1B5E20;
        line-height: 1;
        margin-bottom: .2rem;
    }

    .fams-dash-weather-desc {
        font-size: .92rem;
        font-weight: 600;
        color: #55605a;
    }

    .fams-dash-metric {
        background: #F7FAF7;
        border: 1px solid #eef2ef;
        border-radius: 13px;
        padding: .85rem .5rem;
        text-align: center;
        height: 100%;
    }
    .fams-dash-metric i { font-size: 1.15rem; color: #2E7D32; margin-bottom: .35rem; display: block; }
    .fams-dash-metric small { display: block; color: #8a938d; font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; margin-bottom: .15rem; }
    .fams-dash-metric h5 { margin: 0; font-size: 1.05rem; font-weight: 700; color: #262b27; }

    .fams-dash-alert-badge {
        font-size: .85rem !important;
        padding: .55rem 1.3rem !important;
        border-radius: 999px !important;
        font-weight: 700 !important;
    }

    @media (max-width: 767.98px) {
        .fams-dash-hero { padding: 1.5rem; text-align: center; }
        .fams-dash-hero-inner { justify-content: center; flex-direction: column; }
        .fams-dash-hero-sub { max-width: none; }
    }
</style>

{{-- Welcome hero --}}
<div class="fams-dash-hero">
    <div class="fams-dash-hero-inner">
        <div>
            <div class="fams-dash-hero-greeting">{{ $greeting }}</div>
            <div class="fams-dash-hero-title">Welcome back, {{ $firstName }} <i class="bi bi-emoji-smile"></i></div>
            <p class="fams-dash-hero-sub">Here's a quick look at your farm assistance status, latest announcements, and today's weather.</p>
        </div>
        <div class="fams-dash-hero-icon">
    <i class="bi bi-tree-fill"></i>
</div>
    </div>
</div>

@if(!$farmer || !$farmer->profile_completed)
<div class="fams-dash-alert-wrap mb-4">
    <div class="alert fams-alert fams-alert-warning mb-0">
      <i class="bi bi-exclamation-circle me-2"></i>
      Please <a href="{{ route('farmer.profile') }}" class="fw-semibold">complete your profile</a> to access eligibility, recommendations, and QR code features.
    </div>
</div>
@endif

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="fams-stat fams-dash-stat-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Profile Status</div>
          <div class="fams-stat-value" style="font-size:1.25rem;">{{ $farmer?->profile_completed ? 'Complete' : 'Incomplete' }}</div>
        </div>
        <div class="fams-stat-icon fams-dash-stat-icon-ring {{ $farmer?->profile_completed ? 'green' : 'amber' }}">
          <i class="bi bi-{{ $farmer?->profile_completed ? 'check-circle-fill' : 'hourglass-split' }}"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="fams-stat fams-dash-stat-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Crop Type</div>
          <div class="fams-stat-value" style="font-size:1.25rem;">{{ $farmer?->crop_type ?? 'N/A' }}</div>
        </div>
        <div class="fams-dash-stat-icon crop-icon">
    <i class="bi bi-flower1"></i>
</div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="fams-stat fams-dash-stat-card">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Benefits Received</div>
          <div class="fams-stat-value">{{ $distributions->count() }}</div>
        </div>
        <div class="fams-stat-icon fams-dash-stat-icon-ring blue"><i class="bi bi-gift-fill"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="fams-card fams-dash-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-megaphone me-2 text-fams"></i>Latest Announcements</h6>
        <a href="{{ route('farmer.announcements') }}" class="auth-link small">View all</a>
      </div>
      <div class="fams-card-body p-0">
        @forelse($announcements as $a)
        <div class="fams-list-item fams-dash-list-item">
          <div class="d-flex gap-3 align-items-start">
            <div class="fams-dash-row-icon"><i class="bi bi-megaphone-fill"></i></div>
            <div class="flex-grow-1">
              <strong>{{ $a->title }}</strong>
              <p class="mb-0 small text-muted mt-1">{{ Str::limit($a->content, 90) }}</p>
            </div>
          </div>
        </div>
        @empty
        <div class="fams-empty fams-dash-empty"><i class="bi bi-megaphone"></i>No announcements</div>
        @endforelse
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="fams-card fams-dash-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-box-seam me-2 text-fams"></i>My Distributions</h6>
      </div>
      <div class="fams-card-body p-0">
        @forelse($distributions as $d)
        <div class="fams-list-item fams-dash-list-item d-flex justify-content-between align-items-center">
          <span class="d-flex align-items-center gap-3">
            <span class="fams-dash-row-icon"><i class="bi bi-box-seam-fill"></i></span>
            <strong>{{ $d->program->name }}</strong>
          </span>
          <span class="badge bg-success fams-badge fams-dash-status-pill">
            <i class="bi bi-calendar-check"></i> {{ $d->distributed_at->format('M d, Y') }}
          </span>
        </div>
        @empty
        <div class="fams-empty fams-dash-empty"><i class="bi bi-inbox"></i>No benefits received yet</div>
        @endforelse
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-1">
  <div class="col-lg-6">
    <div class="fams-card fams-dash-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-file-earmark-check me-2 text-fams"></i>Recent Applications</h6>
        <a href="{{ route('farmer.applications.index') }}" class="auth-link small">View all</a>
      </div>
      <div class="fams-card-body p-0">
        @forelse($applications as $application)
        <div class="fams-list-item fams-dash-list-item d-flex justify-content-between align-items-center">
          <span class="d-flex align-items-center gap-3">
            <span class="fams-dash-row-icon"><i class="bi bi-file-earmark-text-fill"></i></span>
            <strong>{{ $application->program->name }}</strong>
          </span>
          @php
              $statusIcon = match($application->status) {
                  'approved' => 'bi-check-circle-fill',
                  'rejected' => 'bi-x-circle-fill',
                  default => 'bi-hourglass-split',
              };
          @endphp
          <span class="badge bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'warning') }} fams-badge fams-dash-status-pill">
            <i class="bi {{ $statusIcon }}"></i> {{ ucfirst($application->status) }}
          </span>
        </div>
        @empty
        <div class="fams-empty fams-dash-empty"><i class="bi bi-file-earmark-plus"></i>No applications yet</div>
        @endforelse
      </div>
    </div>
  </div>
  <div class="col-lg-6">

    <div class="fams-card fams-dash-card">

        <div class="fams-card-header">

            <h6>
                <i class="bi bi-cloud-sun me-2 text-fams"></i>
                Current Weather
            </h6>

        </div>

        <div class="fams-card-body">

            @if($weather)

                <div class="fams-dash-weather-hero">

                    @if($weather->weather_icon)
                        <img
                            src="https://openweathermap.org/img/wn/{{ $weather->weather_icon }}@2x.png"
                            width="80">
                    @endif

                    <div class="fams-dash-weather-temp">
                        {{ $weather->temperature }}°C
                    </div>

                    <div class="fams-dash-weather-desc">
                        {{ ucfirst($weather->description) }}
                    </div>

                </div>

                <div class="row g-2 text-center">

                    <div class="col-6">
                        <div class="fams-dash-metric">
                            <i class="bi bi-droplet-half"></i>
                            <small>Humidity</small>
                            <h5>{{ $weather->humidity }}%</h5>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="fams-dash-metric">
                            <i class="bi bi-wind"></i>
                            <small>Wind Speed</small>
                            <h5>{{ $weather->wind_speed }} m/s</h5>
                        </div>
                    </div>

                    <div class="col-6 mt-2">
                        <div class="fams-dash-metric">
                            <i class="bi bi-speedometer2"></i>
                            <small>Pressure</small>
                            <h5>{{ $weather->pressure }} hPa</h5>
                        </div>
                    </div>

                    <div class="col-6 mt-2">
                        <div class="fams-dash-metric">
                            <i class="bi bi-cloud-rain"></i>
                            <small>Rainfall</small>
                            <h5>{{ $weather->rainfall }} mm</h5>
                        </div>
                    </div>

                </div>

                @php

                    $badge = match($weather->alert_level){

                        'Storm Warning' => 'danger',

                        'Rain Alert' => 'warning',

                        'Extreme Heat' => 'danger',

                        'Strong Wind' => 'primary',

                        default => 'success',

                    };

                @endphp

                <hr>

                <div class="text-center">

                    <span class="badge bg-{{ $badge }} fs-6 fams-dash-alert-badge">

                        <i class="bi bi-shield-exclamation me-1"></i>{{ $weather->alert_level }}

                    </span>

                </div>

            @else

                <div class="fams-dash-empty">
                    <i class="bi bi-cloud-slash"></i>
                    No weather data available.
                </div>

            @endif

        </div>

    </div>

</div>
</div>
@endsection