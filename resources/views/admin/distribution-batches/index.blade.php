@extends('layouts.admin')

@section('page-title', 'Schedules')
@section('page-subtitle', 'Manage benefit distributions and farmer training sessions')

@section('main')
<div class="d-flex flex-wrap gap-2 justify-content-between mb-3">
    <nav class="d-flex flex-wrap gap-2" aria-label="Schedule type">
        @foreach (['' => 'All Schedules', 'distribution' => 'Distributions', 'training' => 'Training'] as $filter => $label)
            <a class="btn {{ ($type ?? '') === $filter ? 'btn-fams' : 'btn-outline-secondary' }}"
               href="{{ route('admin.distribution-batches.index', $filter ? ['type' => $filter] : []) }}">{{ $label }}</a>
        @endforeach
    </nav>
    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-secondary" href="{{ route('admin.distributions.index') }}">Distribution Logs</a>
        <a class="btn btn-fams" href="{{ route('admin.schedules.options') }}"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Create Schedule</a>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">Schedule</th><th scope="col">Type</th><th scope="col">Program</th><th scope="col">Date</th><th scope="col">Status</th></tr></thead>
            <tbody>
                @forelse ($schedules as $schedule)
                    <tr>
                        <td><a class="fw-semibold" href="{{ route($schedule->type === 'training' ? 'admin.trainings.show' : 'admin.distribution-batches.show', $schedule->id) }}">{{ $schedule->title }}</a></td>
                        <td><span class="badge {{ $schedule->type === 'training' ? 'text-bg-info' : 'text-bg-success' }}">{{ ucfirst($schedule->type) }}</span></td>
                        <td>{{ $schedule->program }}<div class="small text-muted">{{ $schedule->detail }}</div></td>
                        <td>{{ \Illuminate\Support\Carbon::parse($schedule->scheduled_date)->format('M j, Y') }}</td>
                        <td><span class="badge {{ $schedule->status === 'published' ? 'text-bg-success' : 'text-bg-light' }}">{{ ucfirst($schedule->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">No schedules to display.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($schedules->hasPages())
        <div class="card-footer">{{ $schedules->links() }}</div>
    @endif
</div>
@endsection
