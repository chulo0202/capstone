@extends('layouts.farmer')

@section('page-title', 'Eligibility Status')

@section('main')
@if(!$farmer || !$farmer->profile_completed)
<div class="alert alert-warning">Complete your <a href="{{ route('farmer.profile') }}">profile</a> to view eligibility status.</div>
@else
<div class="row g-3">
  @foreach($results as $item)
  <div class="col-md-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start mb-2">
          <h6 class="mb-0">{{ $item['program']->name }}</h6>
          <span class="badge bg-{{ $item['status'] === 'eligible' ? 'success' : ($item['status'] === 'partially_eligible' ? 'warning text-dark' : 'danger') }}">
            {{ str_replace('_', ' ', ucfirst($item['status'])) }}
          </span>
        </div>
        <p class="small text-muted">{{ Str::limit($item['program']->description, 80) }}</p>
        @if(count($item['missing']))
        <p class="small mb-0"><strong>Missing:</strong> {{ implode('; ', $item['missing']) }}</p>
        @else
        <p class="small text-success mb-0">All requirements met</p>
        @endif
      </div>
    </div>
  </div>
  @endforeach
</div>
@endif
@endsection
