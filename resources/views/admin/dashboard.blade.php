@extends('layouts.admin')

@section('page-title', 'Dashboard')
@section('page-subtitle', 'Overview of farmers, programs, and distributions')

@section('main')
<div class="row g-3 mb-4">
  <div class="col-sm-6 col-xl-3">
    <div class="fams-stat">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Total Farmers</div>
          <div class="fams-stat-value">{{ $totalFarmers }}</div>
        </div>
        <div class="fams-stat-icon green"><i class="bi bi-people-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="fams-stat">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Active Programs</div>
          <div class="fams-stat-value">{{ $totalPrograms }}</div>
        </div>
        <div class="fams-stat-icon blue"><i class="bi bi-journal-bookmark-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="fams-stat">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Beneficiaries</div>
          <div class="fams-stat-value">{{ $totalBeneficiaries }}</div>
        </div>
        <div class="fams-stat-icon amber"><i class="bi bi-gift-fill"></i></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-xl-3">
    <div class="fams-stat">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Weather</div>
          <div class="fams-stat-value">{{ $weather?->temperature }}°C</div>
          <div class="fams-stat-sub">{{ ucfirst($weather?->description ?? 'N/A') }}</div>
        </div>
        <div class="fams-stat-icon teal"><i class="bi bi-cloud-sun-fill"></i></div>
      </div>
    </div>
  </div>
</div>

@if($weather && ($weather->rain_alert || $weather->storm_alert))
<div class="alert fams-alert fams-alert-warning mb-4">
  <i class="bi bi-exclamation-triangle-fill me-2"></i>
  <strong>Weather Alert:</strong>
  @if($weather->storm_alert) Storm conditions detected. @endif
  @if($weather->rain_alert) Rain expected. @endif
  {{ $weather->advisory }}
</div>
@endif

<div class="row g-3">
  <div class="col-lg-6">
    <div class="fams-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-box-seam me-2 text-fams"></i>Recent Distributions</h6>
      </div>
      <div class="fams-card-body p-0">
        <div class="table-responsive">
          <table class="table fams-table mb-0">
            <thead><tr><th>Farmer</th><th>Program</th><th>Date</th></tr></thead>
            <tbody>
              @forelse($recentDistributions as $dist)
              <tr>
                <td><strong>{{ $dist->farmer->full_name }}</strong></td>
                <td>{{ $dist->program->name }}</td>
                <td><span class="text-muted">{{ $dist->distributed_at->format('M d, Y') }}</span></td>
              </tr>
              @empty
              <tr><td colspan="3"><div class="fams-empty"><i class="bi bi-inbox"></i>No distributions yet</div></td></tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="fams-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-megaphone me-2 text-fams"></i>Recent Announcements</h6>
      </div>
      <div class="fams-card-body p-0">
        @forelse($recentAnnouncements as $announcement)
        <div class="fams-list-item">
          <strong>{{ $announcement->title }}</strong>
          <p class="mb-0 small text-muted mt-1">{{ Str::limit($announcement->content, 90) }}</p>
        </div>
        @empty
        <div class="fams-empty"><i class="bi bi-megaphone"></i>No announcements yet</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
