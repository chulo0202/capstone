@extends('layouts.admin')

@section('page-title', 'SMS Notifications')

@section('main')
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white"><h6 class="mb-0">SMS History</h6></div>
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover mb-0">
        <thead class="table-light"><tr><th>Recipient</th><th>Message</th><th>Status</th><th>Submitted At</th><th>Source</th><th>Actions</th></tr></thead>
        <tbody>
          @forelse($notifications as $sms)
          <tr>
            <td>{{ $sms->recipient }}</td>
            <td><small>{{ Str::limit($sms->message, 60) }}</small></td>
            <td>
                @php
                    $statusColor = match ($sms->status) {
                        'sent', 'submitted' => 'success',
                        'queued', 'pending' => 'warning',
                        'failed', 'refunded' => 'danger',
                        default => 'secondary',
                    };
                @endphp

                <span class="badge bg-{{ $statusColor }}">
                    {{ ucfirst($sms->status) }}
                </span>
                @if ($sms->distribution_batch_id && isset($sms->response_data['error']))
                    <small class="d-block text-muted">{{ $sms->response_data['error'] }}</small>
                @endif
            </td>
            <td>{{ $sms->sent_at?->format('M d, Y h:i A') ?? '-' }}</td>
            <td>{{ $sms->distributionBatch?->name ?? $sms->announcement?->title ?? '-' }}</td>
            <td>
                @if ($sms->distributionBatch?->status === 'published' && config('services.distribution_sms.enabled') && in_array($sms->status, ['pending', 'failed'], true))
                    <form method="POST" action="{{ route('admin.sms.retry', $sms) }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-success">{{ $sms->status === 'pending' ? 'Send SMS' : 'Retry SMS' }}</button>
                    </form>
                @endif
            </td>
          </tr>
          @empty
          <tr><td colspan="6" class="text-center text-muted py-4">No SMS records</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
  @if($notifications->hasPages())<div class="card-footer bg-white">{{ $notifications->links() }}</div>@endif
</div>
@endsection
