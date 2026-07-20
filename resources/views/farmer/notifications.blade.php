@extends('layouts.farmer')

@section('page-title', 'Notifications')
@section('page-subtitle', 'Your SMS history, updates, and weather alerts')

@section('main')
<div class="row g-3">
  <div class="col-lg-6">
    <div class="fams-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-chat-left-text me-2 text-fams"></i>SMS History</h6>
      </div>
      <div class="fams-card-body p-0">
        @forelse($notifications as $notification)
        <div class="fams-list-item">
          <strong>{{ $notification->recipient }}</strong>
          <p class="mb-0 small text-muted mt-1">{{ $notification->message }}</p>
        </div>
        @empty
        <div class="fams-empty"><i class="bi bi-chat-left-text"></i>No SMS notifications yet</div>
        @endforelse
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="fams-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-cloud-sun me-2 text-fams"></i>Weather Alerts</h6>
      </div>
      <div class="fams-card-body">
        @if($weather)
        <p class="mb-1"><strong>{{ ucfirst($weather->description) }}</strong></p>
        <p class="mb-1 text-muted">Temperature: {{ $weather->temperature }}°C</p>
        <p class="mb-0 text-muted">Humidity: {{ $weather->humidity }}%</p>
        @else
        <p class="text-muted mb-0">No weather data available.</p>
        @endif
      </div>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="fams-card">
      <div class="fams-card-header">
        <h6><i class="bi bi-megaphone me-2 text-fams"></i>Latest Announcements</h6>
      </div>
      <div class="fams-card-body p-0">
        @forelse($announcements as $announcement)
        <div class="fams-list-item">
          <strong>{{ $announcement->title }}</strong>
          <p class="mb-0 small text-muted mt-1">{{ Str::limit($announcement->content, 90) }}</p>
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
        <h6><i class="bi bi-box-seam me-2 text-fams"></i>Distribution Updates</h6>
      </div>
      <div class="fams-card-body p-0">
        @forelse($distributions as $distribution)
        <div class="fams-list-item">
          <strong>{{ $distribution->program->name }}</strong>
          <p class="mb-0 small text-muted mt-1">Released on {{ $distribution->distributed_at->format('M d, Y') }}</p>
        </div>
        @empty
        <div class="fams-empty"><i class="bi bi-box-seam"></i>No distribution updates</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
