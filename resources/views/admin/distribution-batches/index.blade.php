@extends('layouts.admin')

@section('page-title', 'Distribution Preparation')

@section('main')
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-fams" href="{{ route('admin.distribution-batches.create') }}">Create Distribution</a>
    <a class="btn btn-outline-secondary" href="{{ route('admin.distributions.index') }}">Distribution Logs</a>
</div>

<div class="card border-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Name</th><th>Program and benefit</th><th>Scheduled date</th><th>Status</th></tr></thead>
            <tbody>
                @forelse ($batches as $batch)
                    <tr>
                        <td><a href="{{ route('admin.distribution-batches.show', $batch) }}">{{ $batch->name }}</a></td>
                        <td>{{ $batch->program->name }} · {{ $batch->benefit->name }}</td>
                        <td>{{ $batch->scheduled_date->format('M j, Y') }}</td>
                        <td>{{ ucfirst($batch->status) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4">No distribution batches yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if ($batches->hasPages())
        <div class="card-footer">{{ $batches->links() }}</div>
    @endif
</div>
@endsection
