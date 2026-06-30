@extends('layouts.farmer')

@section('page-title', 'Program Recommendations')

@section('main')
@if(!$farmer || !$farmer->profile_completed)
<div class="alert alert-warning">Complete your <a href="{{ route('farmer.profile') }}">profile</a> to get program recommendations.</div>
@else
<div class="table-responsive">
  <table class="table table-hover bg-white shadow-sm rounded">
    <thead class="table-light">
      <tr><th>Program</th><th>Description</th><th>Status</th><th>Missing Requirements</th></tr>
    </thead>
    <tbody>
      @forelse($recommendations as $rec)
      <tr>
        <td><strong>{{ $rec->program->name }}</strong></td>
        <td><small>{{ Str::limit($rec->program->description, 80) }}</small></td>
        <td>
          <span class="badge bg-{{ $rec->eligibility_status === 'eligible' ? 'success' : ($rec->eligibility_status === 'partially_eligible' ? 'warning text-dark' : 'danger') }}">
            {{ str_replace('_', ' ', ucfirst($rec->eligibility_status)) }}
          </span>
        </td>
        <td><small>{{ $rec->missing_requirements ? implode('; ', $rec->missing_requirements) : 'None' }}</small></td>
      </tr>
      @empty
      <tr><td colspan="4" class="text-center text-muted py-4">No recommendations available</td></tr>
      @endforelse
    </tbody>
  </table>
</div>
@endif
@endsection
