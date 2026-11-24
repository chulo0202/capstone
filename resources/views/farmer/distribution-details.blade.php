@extends('layouts.farmer')

@section('page-title', 'Distribution Details')

@section('main')
<a class="btn btn-fams-back mb-3" href="{{ route('farmer.notifications.index') }}">
    <i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Notifications
</a>

<section class="card shadow-sm border-0">
    <div class="card-body p-3 p-md-4">
        <div class="d-flex gap-3 align-items-start mb-4">
            <span class="rounded-3 bg-success bg-opacity-10 text-success p-3"><i class="bi bi-box-seam fs-4" aria-hidden="true"></i></span>
            <div class="text-break">
                <span class="badge {{ $distribution ? 'text-bg-success' : 'text-bg-primary' }}">
                    {{ $distribution ? 'Benefit Received' : 'Scheduled' }}
                </span>
                <h2 class="h4 mt-2 mb-1">{{ $batch?->name ?? $distribution->program->name }}</h2>
                @if ($applicationId)<div class="small text-muted">Application #{{ $applicationId }}</div>@endif
            </div>
        </div>

        @if ($distribution)
            <div class="alert alert-success">Released on {{ $distribution->distributed_at->format('M j, Y, g:i A') }} ({{ config('app.timezone') }}).</div>
        @endif

        @if ($batch)
            @include('partials.distribution-schedule')
        @else
            <dl class="mb-0">
                <dt>Specific Benefit</dt>
                <dd class="mb-0">{{ $distribution->benefit?->name ?? 'Program-level benefit' }}</dd>
            </dl>
        @endif
    </div>
</section>
@endsection
