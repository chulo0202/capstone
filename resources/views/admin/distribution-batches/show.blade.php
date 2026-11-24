@extends('layouts.admin')

@section('page-title', $batch->name)

@section('main')
<a href="{{ route('admin.distribution-batches.index') }}" class="btn btn-fams-back mb-3">
    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Distribution Batches
</a>

<section class="card shadow-sm mb-3">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
            <h2 class="h5 mb-0"><i class="bi bi-calendar-event me-2 text-success" aria-hidden="true"></i>Distribution Schedule</h2>
            <span class="badge {{ match ($batch->status) {
                'published' => 'text-bg-primary', 'completed' => 'text-bg-success',
                'cancelled' => 'text-bg-danger', default => 'text-bg-secondary',
            } }}">{{ ucfirst($batch->status) }}</span>
        </div>
        @include('partials.distribution-schedule')
    </div>
</section>
@if ($errors->any())
    <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
@endif
@if (in_array($batch->status, ['draft', 'published'], true))
    <section class="card shadow-sm mb-3">
        <div class="card-body p-3 p-md-4">
            <h2 class="h6 mb-3">Manage Schedule</h2>
            <div class="d-flex flex-wrap align-items-center gap-3">
                @if ($batch->status === 'draft')
                    <form method="POST" class="d-flex flex-wrap align-items-center gap-2" action="{{ route('admin.distribution-batches.publish', $batch) }}">
                        @csrf
                        <label class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="confirm_publish" value="1" required> Confirm schedule
                        </label>
                        <button class="btn btn-fams">Publish Schedule</button>
                    </form>
                    <a class="btn btn-outline-secondary" href="{{ route('admin.distribution-batches.edit', $batch) }}">Edit Schedule</a>
                @endif
                <details class="ms-lg-auto">
                    <summary class="text-danger fw-semibold">Cancel Batch</summary>
                    <form method="POST" class="mt-2 d-flex flex-wrap align-items-center gap-2" action="{{ route('admin.distribution-batches.cancel', $batch) }}">
                        @csrf
                        <label class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="confirm_cancel" value="1" required> Confirm cancellation
                        </label>
                        <button class="btn btn-outline-danger">Cancel Batch</button>
                    </form>
                </details>
            </div>
        </div>
    </section>
@endif

<section class="card shadow-sm overflow-hidden">
    <div class="p-3 border-bottom">
        <h2 class="h6 mb-0">Beneficiaries <span class="badge text-bg-light">{{ $batch->assignments->count() }}</span></h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <thead><tr><th>Beneficiary</th><th>Application</th><th>Status</th><th>Release</th></tr></thead>
            <tbody>
                @foreach ($batch->assignments as $assignment)
                    <tr>
                        <td>{{ $assignment->application->farmer?->full_name ?? 'Former farmer' }}</td>
                        <td>#{{ $assignment->application_id }}</td>
                        <td><span class="badge {{ $assignment->released_at ? 'text-bg-success' : ($assignment->cancelled_at ? 'text-bg-danger' : 'text-bg-light') }}">
                            {{ $assignment->released_at ? 'Distributed' : ($assignment->cancelled_at ? 'Cancelled' : 'Awaiting release') }}
                        </span></td>
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
</section>
@endsection
