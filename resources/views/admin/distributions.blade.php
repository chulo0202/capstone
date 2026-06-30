@extends('layouts.admin')

@section('page-title', 'Distribution Monitoring')

@section('main')
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Distribution Logs</h5>
  <a href="{{ route('admin.distributions.scan') }}" class="btn btn-fams"><i class="bi bi-qr-code-scan me-1"></i>Scan QR Code</a>
</div>
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light">
          <tr><th>Farmer</th><th>Program</th><th>Date</th><th>Released By</th><th>Notes</th></tr>
        </thead>
        <tbody>
          @forelse($distributions as $dist)
          <tr>
            <td>{{ $dist->farmer->full_name }}</td>
            <td>{{ $dist->program->name }}</td>
            <td>{{ $dist->distributed_at->format('M d, Y h:i A') }}</td>
            <td>{{ $dist->releasedBy->name }}</td>
            <td>{{ $dist->notes ?? '-' }}</td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted py-4">No distribution records</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($distributions->hasPages())<div class="card-footer bg-white">{{ $distributions->links() }}</div>@endif
</div>
@endsection
