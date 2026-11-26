@extends('layouts.farmer')

@section('page-title', 'Training Sessions')
@section('page-subtitle', 'Your assigned training schedules')

@section('main')
<section class="card shadow-sm">
    <div class="card-body p-3 p-md-4">
        <form method="GET" action="{{ route('farmer.trainings.index') }}" class="row g-2 align-items-end mb-3">
            <div class="col-sm-auto">
                <label for="training-status" class="form-label">Registration status</label>
                <select id="training-status" name="status" class="form-select">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $option)
                        <option value="{{ $option }}" @selected($status === $option)>{{ ucfirst($option) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-fams">Filter</button>
            </div>
        </form>

        <div class="d-grid gap-3">
            @forelse ($registrations as $registration)
                @php($training = $registration->trainingSession)
                <article class="border rounded-3 p-3 d-flex flex-wrap align-items-start gap-3">
                    <span class="rounded-3 bg-success-subtle text-success p-2" aria-hidden="true"><i class="bi bi-calendar-event"></i></span>
                    <div class="col-12 col-sm text-break">
                        <h2 class="h5 mb-1">{{ $training->title }}</h2>
                        <p class="mb-2">{{ \Illuminate\Support\Str::limit($training->objectives, 200) }}</p>
                        <p class="small text-muted mb-2">{{ $training->scheduled_date->format('M j, Y') }} · {{ substr($training->start_time, 0, 5) }}–{{ substr($training->end_time, 0, 5) }} (Philippine time)</p>
                        <span class="badge {{ match ($registration->status) { 'confirmed' => 'text-bg-success', 'rejected' => 'text-bg-danger', default => 'text-bg-warning' } }}">{{ ucfirst($registration->status) }}</span>
                    </div>
                    <a class="btn btn-outline-success align-self-sm-center" href="{{ route('farmer.trainings.registration', $registration) }}" aria-label="View {{ $training->title }}">View</a>
                </article>
            @empty
                <p class="text-muted mb-0">{{ $status ? 'No training sessions match this status.' : 'No training sessions have been assigned to you.' }}</p>
            @endforelse
        </div>
        @if ($registrations->hasPages())
            <div class="mt-3">{{ $registrations->links() }}</div>
        @endif
    </div>
</section>
@endsection
