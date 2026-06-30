@extends('layouts.farmer')

@section('page-title', 'Dashboard')
@section('page-subtitle', 'Your assistance overview and latest updates')

@section('main')
@if(!$farmer || !$farmer->profile_completed)
<div class="alert fams-alert fams-alert-warning mb-4">
  <i class="bi bi-exclamation-circle me-2"></i>
  Please <a href="{{ route('farmer.profile') }}" class="fw-semibold">complete your profile</a> to access eligibility, recommendations, and QR code features.
</div>
@endif

<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="fams-stat">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Profile Status</div>
          <div class="fams-stat-value" style="font-size:1.25rem;">{{ $farmer?->profile_completed ? 'Complete' : 'Incomplete' }}</div>
        </div>
        <div class="fams-stat-icon {{ $farmer?->profile_completed ? 'green' : 'amber' }}">
          <i class="bi bi-{{ $farmer?->profile_completed ? 'check-circle-fill' : 'hourglass-split' }}"></i>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="fams-stat">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Crop Type</div>
          <div class="fams-stat-value" style="font-size:1.25rem;">{{ $farmer?->crop_type ?? 'N/A' }}</div>
        </div>
        <div class="fams-stat-icon green"><i class="bi bi-flower1"></i></div>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="fams-stat">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="fams-stat-label">Benefits Received</div>
          <div class="fams-stat-value">{{ $distributions->count() }}</div>
        </div>
        <div class="fams-stat-icon blue"><i class="bi bi-gift-fill"></i></div>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="fams-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-megaphone me-2 text-fams"></i>Latest Announcements</h6>
        <a href="{{ route('farmer.announcements') }}" class="auth-link small">View all</a>
      </div>
      <div class="fams-card-body p-0">
        @forelse($announcements as $a)
        <div class="fams-list-item">
          <strong>{{ $a->title }}</strong>
          <p class="mb-0 small text-muted mt-1">{{ Str::limit($a->content, 90) }}</p>
        </div>
        @empty
        <div class="fams-empty"><i class="bi bi-megaphone"></i>No announcements</div>
        @endforelse
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="fams-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-box-seam me-2 text-fams"></i>My Distributions</h6>
      </div>
      <div class="fams-card-body p-0">
        @forelse($distributions as $d)
        <div class="fams-list-item d-flex justify-content-between align-items-center">
          <span><strong>{{ $d->program->name }}</strong></span>
          <span class="badge bg-success fams-badge">{{ $d->distributed_at->format('M d, Y') }}</span>
        </div>
        @empty
        <div class="fams-empty"><i class="bi bi-inbox"></i>No benefits received yet</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
