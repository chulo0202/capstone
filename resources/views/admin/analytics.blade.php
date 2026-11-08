@extends('layouts.admin')

@section('title', 'Analytics Dashboard')

@section('main')

<style>
    .analytics-page {
        font-size: 0.9rem;
    }

    .analytics-page .analytics-header h2 {
        font-size: 1.55rem;
    }

    .analytics-page .analytics-header p {
        font-size: 0.82rem;
    }

    .analytics-page .section-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 1rem;
    }

    .analytics-page .card {
        border: 0;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
    }

    .analytics-page .stat-card {
        min-height: 105px;
    }

    .analytics-page .stat-label {
        font-size: 0.78rem;
        color: #6c757d;
        margin-bottom: 5px;
    }

    .analytics-page .stat-value {
        font-size: 1.65rem;
        font-weight: 700;
        line-height: 1.1;
    }

    .analytics-page .chart-card {
        height: 320px;
    }

    .analytics-page .chart-card .card-body {
        padding: 1rem 1.15rem;
    }

    .analytics-page .chart-title {
        font-size: 1rem;
        font-weight: 700;
        margin-bottom: 2px;
    }

    .analytics-page .chart-description {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 8px;
    }

    .analytics-page .chart-wrapper {
        position: relative;
        width: 100%;
        height: 235px;
    }

    .analytics-page .small-chart-card {
        height: 275px;
    }

    .analytics-page .small-chart-wrapper {
        position: relative;
        width: 100%;
        height: 195px;
    }

    .analytics-page .table {
        font-size: 0.8rem;
    }

    .analytics-page .table th {
        font-weight: 600;
        white-space: nowrap;
    }

    .analytics-page .table td {
        vertical-align: middle;
    }

    .analytics-page .period-card {
        font-size: 0.82rem;
    }

    .analytics-page .status-card {
        min-height: 95px;
    }

    .analytics-page .status-card .status-value {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .analytics-page .badge {
        font-size: 0.68rem;
    }

    .analytics-page .weather-card {
        min-height: 125px;
    }

    .analytics-page .weather-value {
        font-size: 1.25rem;
        font-weight: 700;
    }

    .analytics-page .export-btn {
        font-size: 0.78rem;
    }

    /* =========================================================
   PROGRAM PERFORMANCE SUMMARY
   ========================================================= */

  .performance-table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      overflow: hidden;
  }

  .performance-table thead th {
      background: #f7f9fc;
      color: #64748b;
      font-size: 13px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: .04em;
      padding: 13px 12px;
      border-bottom: 1px solid #dbe2ea;
      text-align: center;
      white-space: nowrap;
  }

  .performance-table thead th:first-child {
      text-align: left;
  }

  .performance-table tbody td {
      padding: 14px 12px;
      border-bottom: 1px solid #e2e8f0;
      vertical-align: middle;
      text-align: center;
  }

  .performance-table tbody td:first-child {
      text-align: left;
  }

  .performance-table tbody tr:last-child td {
      border-bottom: none;
  }

  .performance-table tbody tr {
      transition: background .2s ease;
  }

  .performance-table tbody tr:hover {
      background: #f8fafc;
  }

  .program-name {
      font-weight: 700;
      color: #172033;
      font-size: 14px;
  }

  .stat-number {
      font-size: 15px;
      font-weight: 700;
      color: #172033;
  }

  .stat-muted {
      color: #64748b;
      font-size: 13px;
  }

  /* Percentage badge */
  .percentage-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 58px;
      padding: 5px 9px;
      border-radius: 999px;
      background: #eef6ff;
      color: #2563eb;
      font-size: 12px;
      font-weight: 700;
  }

  /* Completion */
  .completion-wrapper {
      min-width: 130px;
  }

  .completion-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
  }

  .completion-percent {
      font-size: 12px;
      font-weight: 700;
      color: #334155;
  }

  .progress-track {
      width: 100%;
      height: 7px;
      background: #e5e7eb;
      border-radius: 999px;
      overflow: hidden;
  }

  .progress-bar {
      height: 100%;
      border-radius: 999px;
      transition: width .3s ease;
  }

  /* Responsive */
  @media (max-width: 900px) {
      .performance-table {
          min-width: 850px;
      }

      .performance-table-wrapper {
          overflow-x: auto;
      }
  }

    @media (max-width: 991px) {
        .analytics-page .chart-card {
            height: 300px;
        }

        .analytics-page .chart-wrapper {
            height: 215px;
        }
    }

    @media (max-width: 767px) {
        .analytics-page .chart-card {
            height: 285px;
        }

        .analytics-page .chart-wrapper {
            height: 205px;
        }
    }
</style>


<div class="analytics-page">

    {{-- =========================================================
         HEADER
    ========================================================== --}}

    <div class="analytics-header d-flex flex-wrap justify-content-between align-items-center mb-3">

        <div>
            <h2 class="fw-bold mb-1">
                Analytics Dashboard
            </h2>

            <p class="text-muted mb-0">
                Farmer, program, distribution, and weather analytics
            </p>
        </div>

        <form
            method="GET"
            action="{{ route('admin.analytics.index') }}"
            class="d-flex align-items-center gap-2 mt-2 mt-md-0"
        >

            <label for="academic_year" class="fw-semibold mb-0">
                Year:
            </label>

            <select
                id="academic_year"
                name="academic_year"
                class="form-select form-select-sm"
                style="width: 145px;"
                onchange="this.form.submit()"
            >

                @foreach($academicYears as $year)

                    <option
                        value="{{ $year }}"
                        @selected($selectedAcademicYear == $year)
                    >
                        {{ $year }}
                    </option>

                @endforeach

            </select>

        </form>

    </div>


    {{-- =========================================================
         SELECTED PERIOD
    ========================================================== --}}

    <div class="card period-card mb-4">

        <div class="card-body py-2 px-3">

            <i class="bi bi-calendar3 me-2"></i>

            <strong>Selected Period:</strong>

            {{ $periodStart->format('F d, Y') }}
            –
            {{ $periodEnd->format('F d, Y') }}

        </div>

    </div>


    {{-- =========================================================
         01 FARMER PROFILE OVERVIEW
    ========================================================== --}}

    <div class="section-title">
        Farmer Profile Overview
    </div>


    {{-- STAT CARDS --}}

    <div class="row g-3 mb-3">

        <div class="col-xl-3 col-md-6">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="stat-label">
                        Total Farmers
                    </div>

                    <div class="stat-value">
                        {{ $totalFarmers }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="stat-label">
                        Association Members
                    </div>

                    <div class="stat-value">
                        {{ $associationMembers }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="stat-label">
                        4Ps Members
                    </div>

                    <div class="stat-value">
                        {{ $fourPsMembers }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card stat-card">

                <div class="card-body">

                    <div class="stat-label">
                        Non-Association Members
                    </div>

                    <div class="stat-value">
                        {{ $nonAssociationMembers }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- FARMERS BY CROP + BARANGAY --}}

    <div class="row g-3 mb-4">

        <div class="col-lg-6">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Farmers by Crop Type
                    </div>

                    <div class="chart-description">
                        Distribution of farmers by primary crop
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="cropChart"></canvas>
                    </div>

                </div>

            </div>

        </div>


        <div class="col-lg-6">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Farmers by Barangay
                    </div>

                    <div class="chart-description">
                        Farmer distribution by barangay
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="barangayChart"></canvas>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- MEMBERSHIP OVERVIEW --}}

    <div class="row g-3 mb-4">

        <div class="col-lg-6">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Membership Overview
                    </div>

                    <div class="chart-description">
                        Association and 4Ps membership distribution
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="membershipChart"></canvas>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         02 PROGRAM & ELIGIBILITY ANALYTICS
    ========================================================== --}}

    <div class="section-title">
        Program & Eligibility Analytics
    </div>


    {{-- PROGRAM STATUS --}}

    <div class="row g-3 mb-3">

        <div class="col-xl-3 col-md-6">

            <div class="card status-card">

                <div class="card-body">

                    <div class="stat-label">
                        Total Programs
                    </div>

                    <div class="status-value">
                        {{ $totalPrograms }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card status-card">

                <div class="card-body">

                    <div class="stat-label">
                        Active Programs
                    </div>

                    <div class="status-value text-success">
                        {{ $activePrograms }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card status-card">

                <div class="card-body">

                    <div class="stat-label">
                        Upcoming Programs
                    </div>

                    <div class="status-value text-primary">
                        {{ $upcomingPrograms }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card status-card">

                <div class="card-body">

                    <div class="stat-label">
                        Inactive Programs
                    </div>

                    <div class="status-value text-secondary">
                        {{ $inactivePrograms }}
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- ELIGIBILITY + PROGRAM RECOMMENDATION --}}

    <div class="row g-3 mb-4">

        <div class="col-lg-6">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Eligibility Statistics
                    </div>

                    <div class="chart-description">
                        Farmer eligibility classification
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="eligibilityChart"></canvas>
                    </div>

                </div>

            </div>

        </div>


        <div class="col-lg-6">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Program Recommendation Overview
                    </div>

                    <div class="chart-description">
                        Eligibility status by program
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="programRecommendationChart"></canvas>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- PROGRAM ENROLLMENT --}}

    <div class="row g-3 mb-4">

        <div class="col-lg-8">

            <div class="card small-chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Program Enrollment
                    </div>

                    <div class="chart-description">
                        Applications received per program
                    </div>

                    <div class="small-chart-wrapper">
                        <canvas id="enrollmentChart"></canvas>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         03 BENEFIT DISTRIBUTION MONITORING
    ========================================================== --}}

    <div class="section-title">
        Benefit Distribution Monitoring
    </div>


    {{-- DISTRIBUTION CARDS --}}

    <div class="row g-3 mb-3">

        <div class="col-xl-3 col-md-6">

            <div class="card status-card">

                <div class="card-body">

                    <div class="stat-label">
                        Total Distributions
                    </div>

                    <div class="status-value">
                        {{ $totalDistributions }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card status-card">

                <div class="card-body">

                    <div class="stat-label">
                        Completed
                    </div>

                    <div class="status-value text-success">
                        {{ $completedDistributions }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card status-card">

                <div class="card-body">

                    <div class="stat-label">
                        Pending
                    </div>

                    <div class="status-value text-warning">
                        {{ $pendingDistributions }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-3 col-md-6">

            <div class="card status-card">

                <div class="card-body">

                    <div class="stat-label">
                        Completion Rate
                    </div>

                    <div class="status-value text-success">
                        {{ number_format($distributionCompletionRate, 1) }}%
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- DISTRIBUTIONS PER PROGRAM + COMPLETION --}}

    <div class="row g-3 mb-4">

        <div class="col-lg-7">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Distributions per Program
                    </div>

                    <div class="chart-description">
                        Number of distributed benefits per program
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="distributionProgramChart"></canvas>
                    </div>

                </div>

            </div>

        </div>


        <div class="col-lg-5">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Distribution Completion
                    </div>

                    <div class="chart-description">
                        Completed versus pending distributions
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="distributionCompletionChart"></canvas>
                    </div>

                </div>

            </div>

        </div>

    </div>

    {{-- =========================================================
         04 PREDICTIVE FARMER NEEDS
    ========================================================== --}}

    <div class="section-title">
        Predictive Farmer Needs
    </div>


    {{-- WEATHER CARDS --}}

    <div class="row g-3 mb-3">

        <div class="col-xl-4 col-md-6">

            <div class="card weather-card">

                <div class="card-body">

                    <div class="stat-label">
                        Weather Alerts
                    </div>

                    <div class="weather-value">
                        {{ $totalWeatherAlerts }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-4 col-md-6">

            <div class="card weather-card">

                <div class="card-body">

                    <div class="stat-label">
                        Farmers at Risk
                    </div>

                    <div class="weather-value">
                        {{ $farmersAtRiskCount }}
                    </div>

                </div>

            </div>

        </div>


        <div class="col-xl-4 col-md-12">

            <div class="card weather-card">

                <div class="card-body">

                    <div class="stat-label">
                        Latest Weather
                    </div>

                    @if($latestWeather)

                        <div class="weather-value">
                            {{ $latestWeather->temperature }}°C
                        </div>

                        <div class="small text-muted">

                            {{ $latestWeather->location }}

                            —

                            {{ $latestWeather->description }}

                        </div>

                    @else

                        <div class="text-muted small">
                            No weather data available.
                        </div>

                    @endif

                </div>

            </div>

        </div>

    </div>


    {{-- WEATHER ALERT LEVELS + AT-RISK FARMERS --}}

    <div class="row g-3 mb-4">

        <div class="col-lg-6">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Weather Alert Levels
                    </div>

                    <div class="chart-description">
                        Distribution of weather alert records
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="weatherAlertChart"></canvas>
                    </div>

                </div>

            </div>

        </div>


        <div class="col-lg-6">

            <div class="card">

                <div class="card-body">

                    <div class="chart-title mb-3">
                        Weather Risk → At-Risk Farmers
                    </div>

                    <div class="table-responsive">

                        <table class="table table-sm table-hover align-middle mb-0">

                            <thead>

                                <tr>
                                    <th>Farmer</th>
                                    <th>Crop</th>
                                    <th>Barangay</th>
                                    <th>Risk</th>
                                </tr>

                            </thead>

                            <tbody>

                                @forelse($farmersAtRisk as $farmer)

                                    <tr>

                                        <td>
                                            {{ $farmer->full_name ?? 'N/A' }}
                                        </td>

                                        <td>
                                            {{ $farmer->crop_type ?? 'N/A' }}
                                        </td>

                                        <td>
                                            {{ $farmer->barangay ?? 'N/A' }}
                                        </td>

                                        <td>

                                            <span class="badge bg-warning text-dark">
                                                At Risk
                                            </span>

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td
                                            colspan="4"
                                            class="text-center text-muted py-3"
                                        >
                                            No farmers currently identified as at risk.
                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- WEATHER HISTORY --}}

    <div class="card mb-4">

        <div class="card-body">

            <div class="chart-title mb-3">
                Weather History
            </div>

            <div class="table-responsive">

                <table class="table table-sm table-hover align-middle mb-0">

                    <thead>

                        <tr>
                            <th>Date</th>
                            <th>Location</th>
                            <th>Temperature</th>
                            <th>Humidity</th>
                            <th>Weather</th>
                            <th>Alert Level</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($weatherHistory as $weather)

                            <tr>

                                <td>
                                    {{ optional($weather->fetched_at)->format('M d, Y H:i') }}
                                </td>

                                <td>
                                    {{ $weather->location ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ $weather->temperature ?? 'N/A' }}°C
                                </td>

                                <td>
                                    {{ $weather->humidity ?? 'N/A' }}%
                                </td>

                                <td>
                                    {{ $weather->description ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ $weather->alert_level ?? 'Normal' }}
                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="6"
                                    class="text-center text-muted py-3"
                                >
                                    No weather history available.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    {{-- =========================================================
     PROGRAM PERFORMANCE SUMMARY
    ========================================================= --}}

    <div class="analytics-card performance-card">

        <div class="card-header-row">
            <div>
                <h2>Program Performance Summary</h2>
                <p class="card-description">
                    Overview of program applications, eligible farmers,
                    and benefit distributions for
                    {{ str_replace('-', '–', $selectedAcademicYear) }}.
                </p>
            </div>

            <div class="card-header-icon">
                <span></span>
            </div>
        </div>

        <div class="performance-table-wrapper">

            <table class="performance-table">

                <thead>
                    <tr>
                        <th>Program</th>
                        <th>Applications</th>
                        <th>Eligible</th>
                        <th>Eligible %</th>
                        <th>Distributions</th>
                        <th>Completion Rate</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($programPerformanceSummary as $item)

                        @php
                            $applications = (int) $item->applications_count;
                            $eligible = (int) $item->eligible_count;
                            $distributions = (int) $item->distributions_count;

                            /*
                            * Eligible percentage is based on the total
                            * farmers included in the selected period.
                            */
                            $eligiblePercentage = $totalFarmers > 0
                                ? round(($eligible / $totalFarmers) * 100, 2)
                                : 0;

                            /*
                            * Program completion:
                            * distributed benefits compared with eligible
                            * farmers for this program.
                            */
                            $completionRate = $eligible > 0
                                ? round(($distributions / $eligible) * 100, 2)
                                : 0;

                            /*
                            * Prevent the progress bar from visually
                            * exceeding 100%.
                            */
                            $progressWidth = min($completionRate, 100);
                        @endphp

                        <tr>

                            {{-- PROGRAM --}}
                            <td>
                                <div class="program-name">
                                    {{ optional($item->program)->name ?? 'N/A' }}
                                </div>
                            </td>

                            {{-- APPLICATIONS --}}
                            <td>
                                <span class="stat-number">
                                    {{ number_format($applications) }}
                                </span>
                            </td>

                            {{-- ELIGIBLE --}}
                            <td>
                                <span class="stat-number">
                                    {{ number_format($eligible) }}
                                </span>
                            </td>

                            {{-- ELIGIBLE % --}}
                            <td>
                                <span class="percentage-badge">
                                    {{ number_format($eligiblePercentage, 2) }}%
                                </span>
                            </td>

                            {{-- DISTRIBUTIONS --}}
                            <td>
                                <span class="stat-number">
                                    {{ number_format($distributions) }}
                                </span>
                            </td>

                            {{-- COMPLETION RATE --}}
                            <td>
                                <div class="completion-wrapper">

                                    <div class="completion-header">

                                        <span class="completion-percent">
                                            {{ number_format($completionRate, 2) }}%
                                        </span>

                                        <span class="stat-muted">
                                            {{ $distributions }}/{{ $eligible }}
                                        </span>

                                    </div>

                                    <div class="progress-track">

                                        <div
                                            class="progress-bar"
                                            style="width: {{ $progressWidth }}%; background: {{ $completionRate >= 100 ? '#16a34a' : ($completionRate >= 50 ? '#f59e0b' : '#3b82f6') }};"
                                        ></div>

                                    </div>

                                </div>
                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" style="text-align:center; padding:30px;">
                                <div style="font-size:30px; margin-bottom:8px;">
                                    📊
                                </div>

                                <strong>No program performance data</strong>

                                <div class="stat-muted">
                                    There are no records for the selected period.
                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    {{-- RECENT DISTRIBUTION LOGS --}}

    <div class="card mb-4">

        <div class="card-body">

            <div class="chart-title mb-3">
                Recent Distribution Logs
            </div>

            <div class="table-responsive">

                <table class="table table-sm table-hover align-middle mb-0">

                    <thead>

                        <tr>
                            <th>Farmer</th>
                            <th>Program</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                        @forelse($distributionLogs as $distribution)

                            <tr>

                                <td>
                                    {{ $distribution->farmer->full_name ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ $distribution->program->name ?? 'N/A' }}
                                </td>

                                <td>
                                    {{ optional($distribution->created_at)->format('M d, Y') }}
                                </td>

                                <td>

                                    @if($distribution->distributed_at)

                                        <span class="badge bg-success">
                                            Completed
                                        </span>

                                    @else

                                        <span class="badge bg-warning text-dark">
                                            Pending
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>

                                <td
                                    colspan="4"
                                    class="text-center text-muted py-3"
                                >
                                    No recent distribution logs.
                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    {{-- =========================================================
         05 DESCRIPTIVE ANALYTICS & TRENDS
    ========================================================== --}}

    <div class="section-title">
        Descriptive Analytics & Trends
    </div>


    {{-- FARMER REGISTRATION TREND + DISTRIBUTION TREND --}}

    <div class="row g-3 mb-4">

        <div class="col-lg-6">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Farmer Registration Trend
                    </div>

                    <div class="chart-description">
                        Farmer registrations during selected period
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="farmerTrendChart"></canvas>
                    </div>

                </div>

            </div>

        </div>


        <div class="col-lg-6">

            <div class="card chart-card">

                <div class="card-body">

                    <div class="chart-title">
                        Distribution Trend
                    </div>

                    <div class="chart-description">
                        Distribution activity during selected period
                    </div>

                    <div class="chart-wrapper">
                        <canvas id="distributionTrendChart"></canvas>
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- =========================================================
         EXPORTABLE REPORTS
    ========================================================== --}}

    <div class="section-title">
        Exportable Reports
    </div>


    <div class="card mb-4">

        <div class="card-body d-flex flex-wrap gap-2">

            <a
                href="{{ route('admin.reports.pdf', ['academic_year' => $selectedAcademicYear]) }}"
                class="btn btn-outline-danger export-btn"
            >

                <i class="bi bi-file-earmark-pdf me-1"></i>

                Export PDF

            </a>


            <a
                href="{{ route('admin.reports.excel', ['academic_year' => $selectedAcademicYear]) }}"
                class="btn btn-outline-success export-btn"
            >

                <i class="bi bi-file-earmark-spreadsheet me-1"></i>

                Export Excel

            </a>

        </div>

    </div>

</div>


{{-- =========================================================
     CHART.JS
========================================================== --}}

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.2.0"></script>


<script>

document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Register Percentage Plugin
    |--------------------------------------------------------------------------
    */

    Chart.register(ChartDataLabels);


    /*
    |--------------------------------------------------------------------------
    | Helper
    |--------------------------------------------------------------------------
    */

    function createChart(id, config)
    {
        const canvas = document.getElementById(id);

        if (!canvas) {
            return;
        }

        new Chart(canvas, config);
    }


    /*
    |--------------------------------------------------------------------------
    | Common Doughnut Options
    |--------------------------------------------------------------------------
    */

    function doughnutOptions()
    {
        return {

            responsive: true,

            maintainAspectRatio: false,

            cutout: '64%',

            layout: {
                padding: 5
            },

            plugins: {

                legend: {

                    position: 'bottom',

                    labels: {

                        boxWidth: 10,

                        padding: 8,

                        font: {
                            size: 10
                        }

                    }

                },

                datalabels: {

                    color: '#ffffff',

                    font: {

                        weight: 'bold',

                        size: 10

                    },

                    formatter: function (value, context)
                    {

                        const data =
                            context.chart.data.datasets[0].data;

                        const total =
                            data.reduce(
                                (sum, item) =>
                                    sum + Number(item),
                                0
                            );

                        if (!total || !value) {
                            return '';
                        }

                        const percentage =
                            (Number(value) / total) * 100;

                        return percentage.toFixed(1) + '%';
                    }

                }

            }

        };
    }


    /*
    |--------------------------------------------------------------------------
    | Farmers by Crop Type
    |--------------------------------------------------------------------------
    */

    createChart('cropChart', {

        type: 'doughnut',

        data: {

            labels: @json(
                $farmersPerCrop
                    ->pluck('crop_type')
                    ->values()
            ),

            datasets: [{

                data: @json(
                    $farmersPerCrop
                        ->pluck('total')
                        ->values()
                )

            }]

        },

        options: doughnutOptions()

    });


    /*
    |--------------------------------------------------------------------------
    | Farmers by Barangay
    |--------------------------------------------------------------------------
    */

    createChart('barangayChart', {

        type: 'bar',

        data: {

            labels: @json(
                $farmersPerBarangay
                    ->pluck('barangay')
                    ->values()
            ),

            datasets: [{

                label: 'Farmers',

                data: @json(
                    $farmersPerBarangay
                        ->pluck('total')
                        ->values()
                )

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Membership Overview
    |--------------------------------------------------------------------------
    */

    createChart('membershipChart', {

        type: 'doughnut',

        data: {

            labels: [

                'Association Members',
                'Non-Association Members',
                '4Ps Members',
                'Non-4Ps Members'

            ],

            datasets: [{

                data: [

                    {{ $associationMembers }},
                    {{ $nonAssociationMembers }},
                    {{ $fourPsMembers }},
                    {{ $nonFourPsMembers }}

                ]

            }]

        },

        options: doughnutOptions()

    });


    /*
    |--------------------------------------------------------------------------
    | Eligibility Statistics
    |--------------------------------------------------------------------------
    */

    createChart('eligibilityChart', {

        type: 'doughnut',

        data: {

            labels: @json(
                $eligibilityStatistics
                    ->pluck('label')
                    ->values()
            ),

            datasets: [{

                data: @json(
                    $eligibilityStatistics
                        ->pluck('total')
                        ->values()
                )

            }]

        },

        options: doughnutOptions()

    });


    /*
    |--------------------------------------------------------------------------
    | Program Recommendation Overview
    |--------------------------------------------------------------------------
    */

    createChart('programRecommendationChart', {

        type: 'bar',

        data: {

            labels: @json(
                $programRecommendationOverview
                    ->map(fn ($item) => $item->program->name ?? 'Unknown')
                    ->values()
            ),

            datasets: [

                {

                    label: 'Eligible',

                    data: @json(
                        $programRecommendationOverview
                            ->pluck('eligible')
                            ->values()
                    )

                },

                {

                    label: 'Partially Eligible',

                    data: @json(
                        $programRecommendationOverview
                            ->pluck('partially_eligible')
                            ->values()
                    )

                },

                {

                    label: 'Not Eligible',

                    data: @json(
                        $programRecommendationOverview
                            ->pluck('not_eligible')
                            ->values()
                    )

                }

            ]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Program Enrollment
    |--------------------------------------------------------------------------
    */

    createChart('enrollmentChart', {

        type: 'bar',

        data: {

            labels: @json(
                $programEnrollments
                    ->pluck('name')
                    ->values()
            ),

            datasets: [{

                label: 'Applications',

                data: @json(
                    $programEnrollments
                        ->pluck('applications_count')
                        ->values()
                )

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {

                    display: false

                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Distributions per Program
    |--------------------------------------------------------------------------
    */

    createChart('distributionProgramChart', {

        type: 'bar',

        data: {

            labels: @json(
                $distributionsPerProgram
                    ->pluck('name')
                    ->values()
            ),

            datasets: [{

                label: 'Distributions',

                data: @json(
                    $distributionsPerProgram
                        ->pluck('distributions_count')
                        ->values()
                )

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Distribution Completion
    |--------------------------------------------------------------------------
    */

    createChart('distributionCompletionChart', {

        type: 'doughnut',

        data: {

            labels: [

                'Completed',
                'Pending'

            ],

            datasets: [{

                data: [

                    {{ $completedDistributions }},
                    {{ $pendingDistributions }}

                ]

            }]

        },

        options: doughnutOptions()

    });


    /*
    |--------------------------------------------------------------------------
    | Weather Alert Levels
    |--------------------------------------------------------------------------
    */

    createChart('weatherAlertChart', {

        type: 'doughnut',

        data: {

            labels: @json(
                $weatherAlertsPerLevel
                    ->pluck('alert_level')
                    ->values()
            ),

            datasets: [{

                data: @json(
                    $weatherAlertsPerLevel
                        ->pluck('total')
                        ->values()
                )

            }]

        },

        options: doughnutOptions()

    });


    /*
    |--------------------------------------------------------------------------
    | Farmer Registration Trend
    |--------------------------------------------------------------------------
    */

    createChart('farmerTrendChart', {

        type: 'line',

        data: {

            labels: @json(
                $farmerRegistrationTrend
                    ->pluck('date')
                    ->values()
            ),

            datasets: [{

                label: 'Registered Farmers',

                data: @json(
                    $farmerRegistrationTrend
                        ->pluck('total')
                        ->values()
                ),

                fill: false,

                tension: 0.3

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Distribution Trend
    |--------------------------------------------------------------------------
    */

    createChart('distributionTrendChart', {

        type: 'line',

        data: {

            labels: @json(
                $distributionTrend
                    ->pluck('date')
                    ->values()
            ),

            datasets: [{

                label: 'Distributions',

                data: @json(
                    $distributionTrend
                        ->pluck('total')
                        ->values()
                ),

                fill: false,

                tension: 0.3

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }

    });


    /*
    |--------------------------------------------------------------------------
    | Crop Distribution
    |--------------------------------------------------------------------------
    */

    createChart('cropDistributionChart', {

        type: 'doughnut',

        data: {

            labels: @json(
                $farmersPerCrop
                    ->pluck('crop_type')
                    ->values()
            ),

            datasets: [{

                data: @json(
                    $farmersPerCrop
                        ->pluck('total')
                        ->values()
                )

            }]

        },

        options: doughnutOptions()

    });


    /*
    |--------------------------------------------------------------------------
    | Barangay Distribution
    |--------------------------------------------------------------------------
    */

    createChart('barangayDistributionChart', {

        type: 'bar',

        data: {

            labels: @json(
                $farmersPerBarangay
                    ->pluck('barangay')
                    ->values()
            ),

            datasets: [{

                label: 'Farmers',

                data: @json(
                    $farmersPerBarangay
                        ->pluck('total')
                        ->values()
                )

            }]

        },

        options: {

            responsive: true,

            maintainAspectRatio: false,

            plugins: {

                legend: {
                    display: false
                }

            },

            scales: {

                y: {

                    beginAtZero: true,

                    ticks: {
                        precision: 0
                    }

                }

            }

        }

    });

});

</script>

@endsection