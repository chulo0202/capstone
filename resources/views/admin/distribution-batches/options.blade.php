@extends('layouts.admin')

@section('page-title', 'Create Schedule')
@section('page-subtitle', 'Choose the program you want to schedule')

@section('main')
<a href="{{ route('admin.distribution-batches.index') }}" class="btn btn-fams-back mb-3"><i class="bi bi-arrow-left me-1" aria-hidden="true"></i> Schedules</a>
<div class="row g-3">
    @foreach ([
        ['Seed Subsidies', 'Schedule seed and planting material distribution.', 'bi-box-seam'],
        ['Fertilizer Subsidies', 'Schedule fertilizer distribution for approved beneficiaries.', 'bi-box-seam'],
        ['Training and Technology Demonstrations', 'Publish a training session and review farmer registrations.', 'bi-calendar-event'],
    ] as $index => [$name, $description, $icon])
        <div class="col-md-4">
            <div class="card h-100 shadow-sm">
                <div class="card-body d-flex flex-column p-4">
                    <i class="bi {{ $icon }} text-success fs-3 mb-2" aria-hidden="true"></i>
                    <h2 class="h5">{{ $name }}</h2>
                    <p class="text-muted">{{ $description }}</p>
                    <a class="btn btn-fams mt-auto align-self-start" href="{{ $index === 2 ? route('admin.trainings.create') : route('admin.distribution-batches.create', ['program_name' => $name]) }}">{{ $index === 2 ? 'Create Training' : 'Create Distribution' }}</a>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
