@extends('layouts.admin')

@section('page-title', 'Audit Trail')

@section('main')
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>User</th><th>Action</th><th>Model</th><th>Details</th><th>Date</th></tr></thead>
        <tbody>
          @forelse($logs as $log)
          <tr>
            <td>{{ $log->user->name ?? 'System' }}</td>
            <td>{{ ucfirst($log->action) }}</td>
            <td>{{ $log->model }}</td>
            <td><small>{{ is_array($log->details) ? json_encode($log->details) : $log->details }}</small></td>
            <td>{{ $log->created_at->format('M d, Y h:i A') }}</td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted py-4">No audit logs yet</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($logs->hasPages())<div class="card-footer bg-white">{{ $logs->links() }}</div>@endif
</div>
@endsection
