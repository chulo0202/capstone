@extends('layouts.farmer')

@section('page-title', 'Announcements')

@section('main')
@forelse($announcements as $announcement)
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <h5 class="card-title">{{ $announcement->title }}</h5>
    <p class="card-text">{{ $announcement->content }}</p>
    <small class="text-muted">{{ $announcement->publish_date->format('F d, Y h:i A') }}</small>
  </div>
</div>
@empty
<div class="alert alert-info">No announcements at this time.</div>
@endforelse
@if($announcements->hasPages())<div class="mt-3">{{ $announcements->links() }}</div>@endif
@endsection
