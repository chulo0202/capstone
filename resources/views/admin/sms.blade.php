@extends('layouts.admin')

@section('page-title', 'SMS Notifications')

@section('main')
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white"><h6 class="mb-0">SMS History</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Recipient</th><th>Message</th><th>Status</th><th>Sent At</th><th>Announcement</th></tr></thead>
        <tbody>
          @forelse($notifications as $sms)
          <tr>
            <td>{{ $sms->recipient }}</td>
            <td><small>{{ Str::limit($sms->message, 60) }}</small></td>
            <td><span class="badge bg-{{ $sms->status === 'sent' ? 'success' : ($sms->status === 'pending' ? 'warning' : 'danger') }}">{{ ucfirst($sms->status) }}</span></td>
            <td>{{ $sms->sent_at?->format('M d, Y h:i A') ?? '-' }}</td>
            <td>{{ $sms->announcement?->title ?? '-' }}</td>
          </tr>
          @empty
          <tr><td colspan="5" class="text-center text-muted py-4">No SMS records</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($notifications->hasPages())<div class="card-footer bg-white">{{ $notifications->links() }}</div>@endif
</div>
@endsection
