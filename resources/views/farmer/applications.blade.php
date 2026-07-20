@extends('layouts.farmer')

@section('page-title', 'My Applications')
@section('page-subtitle', 'Track your submitted assistance applications')

@section('main')
<div class="fams-card">
  <div class="fams-card-header">
    <h6><i class="bi bi-file-earmark-check me-2 text-fams"></i>Application History</h6>
  </div>
  <div class="fams-card-body p-0">
    <div class="table-responsive">
      <table class="table fams-table mb-0">
        <thead>
          <tr>
            <th>Program</th>
            <th>Date Applied</th>
            <th>Status</th>
            <th>Remarks</th>
          </tr>
        </thead>
        <tbody>
          @forelse($applications as $application)
          <tr>
            <td><strong>{{ $application->program->name }}</strong></td>
            <td>{{ $application->applied_at?->format('M d, Y h:i A') ?? '-' }}</td>
            <td><span class="badge bg-{{ $application->status === 'approved' ? 'success' : ($application->status === 'rejected' ? 'danger' : 'warning') }}">{{ ucfirst($application->status) }}</span></td>
            <td>{{ $application->remarks ?? '-' }}</td>
          </tr>
          @empty
          <tr><td colspan="4" class="text-center text-muted py-4">No applications yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
