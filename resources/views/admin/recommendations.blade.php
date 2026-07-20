@extends('layouts.admin')

@section('page-title', 'Recommendation Engine')

@section('main')
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Farmer</th><th>Program</th><th>Status</th><th>Reason</th></tr></thead>
        <tbody>
          @forelse($recommendations as $recommendation)
          <tr>
            <td>{{ $recommendation->farmer->full_name }}</td>
            <td>{{ $recommendation->program->name }}</td>
            <td><span class="badge bg-{{ $recommendation->eligibility_status === 'eligible' ? 'success' : ($recommendation->eligibility_status === 'partially_eligible' ? 'warning text-dark' : 'danger') }}">{{ str_replace('_', ' ', ucfirst($recommendation->eligibility_status)) }}</span></td>
            <td>{{ $recommendation->missing_requirements ? implode(', ', $recommendation->missing_requirements) : 'All requirements met' }}</td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-center text-muted py-4">No recommendations available</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($recommendations->hasPages())<div class="card-footer bg-white">{{ $recommendations->links() }}</div>@endif
</div>
@endsection
