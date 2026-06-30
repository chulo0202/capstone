@extends('layouts.admin')

@section('page-title', 'Weather Monitoring')

@section('main')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Weather & Advisories</h5>
  <form method="POST" action="{{ route('admin.weather.refresh') }}">@csrf<button class="btn btn-fams"><i class="bi bi-arrow-clockwise me-1"></i>Refresh</button></form>
</div>

@if($current)
<div class="row g-3 mb-4">
  <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><p class="text-muted mb-1">Temperature</p><h3>{{ $current->temperature }}°C</h3></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><p class="text-muted mb-1">Humidity</p><h3>{{ $current->humidity }}%</h3></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><p class="text-muted mb-1">Wind Speed</p><h3>{{ $current->wind_speed }} m/s</h3></div></div></div>
  <div class="col-md-3"><div class="card border-0 shadow-sm"><div class="card-body text-center"><p class="text-muted mb-1">Condition</p><h5>{{ ucfirst($current->description) }}</h5></div></div></div>
</div>

@if($current->rain_alert || $current->storm_alert)
<div class="alert alert-{{ $current->storm_alert ? 'danger' : 'warning' }}">
  <i class="bi bi-exclamation-triangle me-2"></i>
  @if($current->storm_alert)<strong>Storm Alert!</strong> @endif
  @if($current->rain_alert)<strong>Rain Alert!</strong> @endif
</div>
@endif

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white"><h6 class="mb-0">Farmer Advisory</h6></div>
  <div class="card-body">{{ $current->advisory }}</div>
</div>
@else
<div class="alert alert-info">No weather data available. Click Refresh to fetch data.</div>
@endif

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white"><h6 class="mb-0">Weather History</h6></div>
  <div class="table-responsive">
    <table class="table table-hover mb-0">
      <thead class="table-light"><tr><th>Location</th><th>Temp</th><th>Condition</th><th>Alerts</th><th>Fetched</th></tr></thead>
      <tbody>
        @forelse($history as $w)
        <tr>
          <td>{{ $w->location }}</td>
          <td>{{ $w->temperature }}°C</td>
          <td>{{ ucfirst($w->description) }}</td>
          <td>
            @if($w->rain_alert)<span class="badge bg-warning">Rain</span>@endif
            @if($w->storm_alert)<span class="badge bg-danger">Storm</span>@endif
            @if(!$w->rain_alert && !$w->storm_alert)<span class="badge bg-success">Clear</span>@endif
          </td>
          <td>{{ $w->fetched_at->format('M d, Y h:i A') }}</td>
        </tr>
        @empty
        <tr><td colspan="5" class="text-center text-muted py-3">No history</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection
