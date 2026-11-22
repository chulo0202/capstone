@extends('layouts.admin')

@section('page-title', $batch->name)

@section('main')
<a href="{{ route('admin.distribution-batches.index') }}" class="btn btn-outline-secondary mb-3">Back to Distribution Batches</a>
<div class="card card-body mb-3">
    <h2 class="h5">{{ ucfirst($batch->status) }}</h2>
    <div>{{ $batch->program->name }} · {{ $batch->benefit->name }}</div>
    <div>{{ $batch->scheduled_date->format('M j, Y') }} · {{ substr($batch->start_time, 0, 5) }}–{{ substr($batch->end_time, 0, 5) }} ({{ config('app.timezone') }})</div>
    <div>{{ $batch->location }}</div>
    @if ($batch->instructions)<p class="mt-2 mb-0">{{ $batch->instructions }}</p>@endif
</div>
@if ($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif
@if (in_array($batch->status, ['draft', 'published'], true))
    <div class="d-flex flex-wrap gap-3 mb-3">
        @if ($batch->status === 'draft')
            <a class="btn btn-outline-secondary" href="{{ route('admin.distribution-batches.edit', $batch) }}">Edit Schedule</a>
            <form method="POST" action="{{ route('admin.distribution-batches.publish', $batch) }}">
                @csrf
                <label class="me-2"><input type="checkbox" name="confirm_publish" value="1" required> Confirm schedule</label>
                <button class="btn btn-fams">Publish</button>
            </form>
        @endif
        <form method="POST" action="{{ route('admin.distribution-batches.cancel', $batch) }}">
            @csrf
            <label class="me-2"><input type="checkbox" name="confirm_cancel" value="1" required> Confirm cancellation</label>
            <button class="btn btn-outline-danger">Cancel Batch</button>
        </form>
    </div>
@endif
<div class="card border-0">
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Beneficiary</th><th>Application</th><th>Status</th><th>Release</th></tr></thead>
            <tbody>
                @foreach ($batch->assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->application->farmer?->full_name ?? 'Former farmer' }}</td>
                        <td>#{{ $assignment->application_id }}</td>
                        <td>{{ $assignment->released_at ? 'Distributed' : ($assignment->cancelled_at ? 'Cancelled' : 'Awaiting release') }}</td>
                        <td>
                            @if ($batch->canRelease() && $assignment->active_application_id && !$assignment->released_at)
                                <form method="POST" action="{{ route('admin.distributions.release') }}">
                                    @csrf
                                    <input type="hidden" name="application_id" value="{{ $assignment->application_id }}">
                                    <input type="hidden" name="distribution_batch_id" value="{{ $batch->id }}">
                                    <label class="me-2"><input type="checkbox" name="confirm_release" value="1" required> Confirm handover</label>
                                    <button class="btn btn-sm btn-fams">Release</button>
                                </form>
                            @elseif ($assignment->released_at)
                                {{ $assignment->released_at->format('M j, Y H:i') }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
